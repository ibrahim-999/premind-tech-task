import { Field, Input } from '@/shared/components/Input'
import type { FieldSchema } from '../registry'

interface Props {
  fields: FieldSchema[]
  values: Record<string, unknown>
  onChange: (next: Record<string, unknown>) => void
}

export function DynamicConfigFields({ fields, values, onChange }: Props) {
  if (fields.length === 0) {
    return <p className="text-xs italic text-gray-500">No additional configuration.</p>
  }

  const set = (key: string, val: unknown) => {
    onChange({ ...values, [key]: val })
  }

  return (
    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
      {fields.map((f) => {
        const raw = values[f.key]
        if (f.type === 'number') {
          return (
            <Field key={f.key} label={f.label}>
              <Input
                type="number"
                placeholder={f.placeholder}
                value={typeof raw === 'number' ? raw : ''}
                onChange={(e) => {
                  const v = e.target.value
                  set(f.key, v === '' ? undefined : Number(v))
                }}
              />
            </Field>
          )
        }
        if (f.type === 'string-list') {
          const list = Array.isArray(raw) ? raw.join(', ') : ''
          return (
            <Field key={f.key} label={f.label} className="sm:col-span-2">
              <Input
                placeholder={f.placeholder}
                value={list}
                onChange={(e) => {
                  const items = e.target.value
                    .split(',')
                    .map((x) => x.trim())
                    .filter((x) => x !== '')
                  set(f.key, items)
                }}
              />
            </Field>
          )
        }
        return (
          <Field key={f.key} label={f.label}>
            <Input
              placeholder={f.placeholder}
              value={typeof raw === 'string' ? raw : ''}
              onChange={(e) => {
                set(f.key, e.target.value)
              }}
            />
          </Field>
        )
      })}
    </div>
  )
}
