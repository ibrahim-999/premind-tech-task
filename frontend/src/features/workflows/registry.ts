export interface FieldSchema {
  key: string
  label: string
  type: 'string' | 'number' | 'string-list'
  placeholder?: string
}

export interface TypeDefinition {
  type: string
  label: string
  fields: FieldSchema[]
}

export const conditionTypes: TypeDefinition[] = [
  {
    type: 'amount_gte',
    label: 'Amount ≥ threshold',
    fields: [{ key: 'amount', label: 'Threshold', type: 'number', placeholder: '5000' }],
  },
  {
    type: 'amount_lte',
    label: 'Amount ≤ threshold',
    fields: [{ key: 'amount', label: 'Threshold', type: 'number', placeholder: '5000' }],
  },
  {
    type: 'amount_between',
    label: 'Amount in range',
    fields: [
      { key: 'min', label: 'Min', type: 'number', placeholder: '0' },
      { key: 'max', label: 'Max', type: 'number', placeholder: '10000' },
    ],
  },
  {
    type: 'field_eq',
    label: 'Field equals value',
    fields: [
      { key: 'field', label: 'Field', type: 'string', placeholder: 'category' },
      { key: 'value', label: 'Value', type: 'string', placeholder: 'IT' },
    ],
  },
  {
    type: 'field_in',
    label: 'Field is one of',
    fields: [
      { key: 'field', label: 'Field', type: 'string', placeholder: 'category' },
      {
        key: 'values',
        label: 'Values (comma-separated)',
        type: 'string-list',
        placeholder: 'IT, Operations',
      },
    ],
  },
  {
    type: 'always_true',
    label: 'Always (no condition)',
    fields: [],
  },
]

export const resolverTypes: TypeDefinition[] = [
  {
    type: 'direct_manager',
    label: 'Direct manager of submitter',
    fields: [],
  },
  {
    type: 'department_head',
    label: 'Head of submitter’s department',
    fields: [],
  },
  {
    type: 'role',
    label: 'Anyone with role',
    fields: [{ key: 'role', label: 'Role', type: 'string', placeholder: 'finance_head' }],
  },
  {
    type: 'specific_user',
    label: 'Specific user (by id)',
    fields: [{ key: 'user_id', label: 'User ID', type: 'number', placeholder: '1' }],
  },
]

export const approvalModes: Array<{ value: string; label: string }> = [
  { value: 'single', label: 'Single — one approver' },
  { value: 'parallel_any', label: 'Parallel any — any one of N' },
  { value: 'parallel_all', label: 'Parallel all — every assignee' },
  { value: 'quorum', label: 'Quorum — N of M' },
]

export function findConditionType(type: string): TypeDefinition | undefined {
  return conditionTypes.find((c) => c.type === type)
}

export function findResolverType(type: string): TypeDefinition | undefined {
  return resolverTypes.find((r) => r.type === type)
}
