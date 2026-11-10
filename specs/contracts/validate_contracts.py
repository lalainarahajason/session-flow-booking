#!/usr/bin/env python3
"""Dependency-free structural checks and fixture checks for this schema subset.
Not a replacement for an OpenAPI/JSON Schema reference validator or runtime tests.
"""
import json
import re
import uuid
from datetime import datetime
from pathlib import Path

ROOT = Path(__file__).resolve().parent
SPEC = json.loads((ROOT / 'openapi.json').read_text())


def resolve(ref):
    assert ref.startswith('#/'), ref
    value = SPEC
    for key in ref[2:].split('/'):
        value = value[key.replace('~1', '/').replace('~0', '~')]
    return value


def walk(value):
    if isinstance(value, dict):
        yield value
        for item in value.values():
            yield from walk(item)
    elif isinstance(value, list):
        for item in value:
            yield from walk(item)


def valid(value, schema):
    if '$ref' in schema:
        return valid(value, resolve(schema['$ref']))
    if 'oneOf' in schema:
        return sum(valid(value, item) for item in schema['oneOf']) == 1
    if 'anyOf' in schema:
        return any(valid(value, item) for item in schema['anyOf'])
    if 'const' in schema and value != schema['const']:
        return False
    if 'enum' in schema and value not in schema['enum']:
        return False
    kind = schema.get('type')
    if kind == 'object':
        if not isinstance(value, dict):
            return False
        props = schema.get('properties', {})
        return (all(k in value for k in schema.get('required', []))
                and (schema.get('additionalProperties') is not False or set(value) <= set(props))
                and all(valid(v, props[k]) for k, v in value.items() if k in props))
    if kind == 'array':
        return (isinstance(value, list) and schema.get('minItems', 0) <= len(value) <= schema.get('maxItems', float('inf'))
                and all(valid(x, schema['items']) for x in value))
    if kind == 'integer':
        return type(value) is int and schema.get('minimum', -float('inf')) <= value <= schema.get('maximum', float('inf'))
    if kind == 'boolean':
        return type(value) is bool
    if kind == 'null':
        return value is None
    if kind == 'string':
        if not isinstance(value, str) or not schema.get('minLength', 0) <= len(value) <= schema.get('maxLength', float('inf')):
            return False
        if 'pattern' in schema and re.search(schema['pattern'], value) is None:
            return False
        try:
            if schema.get('format') == 'uuid':
                uuid.UUID(value)
            elif schema.get('format') == 'date-time':
                datetime.fromisoformat(value.replace('Z', '+00:00'))
            elif schema.get('format') == 'date':
                datetime.strptime(value, '%Y-%m-%d')
        except ValueError:
            return False
    return True


def main():
    for node in walk(SPEC):
        if '$ref' in node:
            resolve(node['$ref'])
    ids = set()
    for path, operations in SPEC['paths'].items():
        for method, op in operations.items():
            assert op['operationId'] not in ids
            ids.add(op['operationId'])
            assert 'security' in op and 'x-authorization' in op
            params = op.get('parameters', [])
            assert set(re.findall(r'\{([^}]+)\}', path)) == {p['name'] for p in params if p['in'] == 'path'}
            if method != 'get' and path != '/integrations/stripe/events':
                assert any(p['name'] == 'Idempotency-Key' and p['required'] for p in params)
            for alternative in op['security']:
                assert set(alternative) <= set(SPEC['components']['securitySchemes'])
    for schema in SPEC['components']['schemas'].values():
        for node in walk(schema):
            if node.get('type') == 'object':
                assert node.get('additionalProperties') is False
                assert set(node.get('required', [])) <= set(node.get('properties', {}))
    fixtures = json.loads((ROOT / 'examples.json').read_text())
    for fixture in fixtures:
        assert valid(fixture['value'], SPEC['components']['schemas'][fixture['schema']]) == fixture['valid'], fixture['name']
    print(f'OK: {len(ids)} operations, {len(SPEC["components"]["schemas"])} schemas, {len(fixtures)} fixtures; local references and closed objects checked.')
    print('Limits: not full OpenAPI validation; email/URI formats, cross-field business rules and concurrency require additional validation.')


if __name__ == '__main__':
    main()
