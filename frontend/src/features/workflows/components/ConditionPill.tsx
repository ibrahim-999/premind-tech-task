import { Badge } from '@/shared/components/Badge'
import type { WorkflowStepCondition } from '../types'

function describe(c: WorkflowStepCondition): string {
  switch (c.type) {
    case 'amount_gte':
      return `amount ≥ ${String(c.config.amount ?? '?')}`
    case 'amount_lte':
      return `amount ≤ ${String(c.config.amount ?? '?')}`
    case 'amount_between':
      return `amount in [${String(c.config.min ?? '?')}, ${String(c.config.max ?? '?')}]`
    case 'field_eq':
      return `${String(c.config.field ?? 'field')} = ${String(c.config.value ?? '?')}`
    case 'field_in':
      return `${String(c.config.field ?? 'field')} in [${
        Array.isArray(c.config.values) ? c.config.values.join(', ') : '?'
      }]`
    case 'always_true':
      return 'always'
    default:
      return c.type
  }
}

export function ConditionPill({ condition }: { condition: WorkflowStepCondition }) {
  return <Badge tone="amber">when {describe(condition)}</Badge>
}
