import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react'
import { Button } from '@/shared/components/Button'
import { Card, CardBody, CardHeader } from '@/shared/components/Card'
import { Field, Input } from '@/shared/components/Input'
import {
  approvalModes,
  conditionTypes,
  findConditionType,
  findResolverType,
  resolverTypes,
} from '../registry'
import { DynamicConfigFields } from './DynamicConfigFields'

export interface EditorCondition {
  id: string
  type: string
  config: Record<string, unknown>
}

export interface EditorApprover {
  id: string
  resolver_type: string
  config: Record<string, unknown>
}

export interface EditorStep {
  id: string
  name: string
  approval_mode: string
  required_approvals: number
  conditions: EditorCondition[]
  approvers: EditorApprover[]
}

interface Props {
  step: EditorStep
  index: number
  isFirst: boolean
  isLast: boolean
  onChange: (next: EditorStep) => void
  onRemove: () => void
  onMoveUp: () => void
  onMoveDown: () => void
}

export function StepEditor({
  step,
  index,
  isFirst,
  isLast,
  onChange,
  onRemove,
  onMoveUp,
  onMoveDown,
}: Props) {
  const update = (partial: Partial<EditorStep>) => {
    onChange({ ...step, ...partial })
  }

  const addCondition = () => {
    update({
      conditions: [
        ...step.conditions,
        { id: crypto.randomUUID(), type: 'amount_gte', config: {} },
      ],
    })
  }

  const updateCondition = (id: string, next: Partial<EditorCondition>) => {
    update({
      conditions: step.conditions.map((c) => (c.id === id ? { ...c, ...next } : c)),
    })
  }

  const removeCondition = (id: string) => {
    update({ conditions: step.conditions.filter((c) => c.id !== id) })
  }

  const addApprover = () => {
    update({
      approvers: [
        ...step.approvers,
        { id: crypto.randomUUID(), resolver_type: 'direct_manager', config: {} },
      ],
    })
  }

  const updateApprover = (id: string, next: Partial<EditorApprover>) => {
    update({
      approvers: step.approvers.map((a) => (a.id === id ? { ...a, ...next } : a)),
    })
  }

  const removeApprover = (id: string) => {
    update({ approvers: step.approvers.filter((a) => a.id !== id) })
  }

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between gap-3">
          <div className="flex items-center gap-2">
            <span className="flex h-7 w-7 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700">
              {index + 1}
            </span>
            <h3 className="text-sm font-semibold text-gray-900">Step {index + 1}</h3>
          </div>
          <div className="flex items-center gap-1">
            <Button
              type="button"
              variant="ghost"
              size="sm"
              disabled={isFirst}
              onClick={onMoveUp}
              aria-label="Move up"
            >
              <ArrowUp size={14} />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              disabled={isLast}
              onClick={onMoveDown}
              aria-label="Move down"
            >
              <ArrowDown size={14} />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onClick={onRemove}
              aria-label="Remove step"
              className="text-red-600 hover:bg-red-50"
            >
              <Trash2 size={14} />
            </Button>
          </div>
        </div>
      </CardHeader>
      <CardBody className="space-y-5">
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <Field label="Step name">
            <Input
              placeholder="Manager Approval"
              value={step.name}
              onChange={(e) => update({ name: e.target.value })}
            />
          </Field>
          <Field label="Approval mode">
            <select
              value={step.approval_mode}
              onChange={(e) => update({ approval_mode: e.target.value })}
              className="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
            >
              {approvalModes.map((m) => (
                <option key={m.value} value={m.value}>
                  {m.label}
                </option>
              ))}
            </select>
          </Field>
          {step.approval_mode === 'quorum' ? (
            <Field label="Required approvals (N of M)">
              <Input
                type="number"
                min={1}
                value={step.required_approvals}
                onChange={(e) => update({ required_approvals: Number(e.target.value) || 1 })}
              />
            </Field>
          ) : null}
        </div>

        <section>
          <div className="mb-2 flex items-center justify-between">
            <h4 className="text-xs font-semibold uppercase tracking-wide text-gray-500">
              Conditions <span className="font-normal normal-case text-gray-400">(AND-ed; empty = always run)</span>
            </h4>
            <Button type="button" variant="secondary" size="sm" onClick={addCondition}>
              <Plus size={14} /> Add condition
            </Button>
          </div>
          {step.conditions.length === 0 ? (
            <p className="rounded-md border border-dashed border-gray-200 px-3 py-3 text-xs text-gray-500">
              No conditions — this step runs for every subject.
            </p>
          ) : (
            <ul className="space-y-3">
              {step.conditions.map((cond) => {
                const def = findConditionType(cond.type)
                return (
                  <li key={cond.id} className="rounded-md border border-gray-200 bg-gray-50 p-3">
                    <div className="mb-3 flex items-center gap-2">
                      <select
                        value={cond.type}
                        onChange={(e) => updateCondition(cond.id, { type: e.target.value, config: {} })}
                        className="flex-1 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                      >
                        {conditionTypes.map((t) => (
                          <option key={t.type} value={t.type}>
                            {t.label}
                          </option>
                        ))}
                      </select>
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => removeCondition(cond.id)}
                        className="text-red-600 hover:bg-red-50"
                        aria-label="Remove condition"
                      >
                        <Trash2 size={14} />
                      </Button>
                    </div>
                    {def !== undefined ? (
                      <DynamicConfigFields
                        fields={def.fields}
                        values={cond.config}
                        onChange={(config) => updateCondition(cond.id, { config })}
                      />
                    ) : null}
                  </li>
                )
              })}
            </ul>
          )}
        </section>

        <section>
          <div className="mb-2 flex items-center justify-between">
            <h4 className="text-xs font-semibold uppercase tracking-wide text-gray-500">
              Approvers <span className="font-normal normal-case text-gray-400">(union of resolved users)</span>
            </h4>
            <Button type="button" variant="secondary" size="sm" onClick={addApprover}>
              <Plus size={14} /> Add approver
            </Button>
          </div>
          {step.approvers.length === 0 ? (
            <p className="rounded-md border border-dashed border-red-200 bg-red-50 px-3 py-3 text-xs text-red-700">
              At least one approver is required.
            </p>
          ) : (
            <ul className="space-y-3">
              {step.approvers.map((appr) => {
                const def = findResolverType(appr.resolver_type)
                return (
                  <li key={appr.id} className="rounded-md border border-gray-200 bg-gray-50 p-3">
                    <div className="mb-3 flex items-center gap-2">
                      <select
                        value={appr.resolver_type}
                        onChange={(e) =>
                          updateApprover(appr.id, { resolver_type: e.target.value, config: {} })
                        }
                        className="flex-1 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                      >
                        {resolverTypes.map((t) => (
                          <option key={t.type} value={t.type}>
                            {t.label}
                          </option>
                        ))}
                      </select>
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => removeApprover(appr.id)}
                        className="text-red-600 hover:bg-red-50"
                        aria-label="Remove approver"
                      >
                        <Trash2 size={14} />
                      </Button>
                    </div>
                    {def !== undefined ? (
                      <DynamicConfigFields
                        fields={def.fields}
                        values={appr.config}
                        onChange={(config) => updateApprover(appr.id, { config })}
                      />
                    ) : null}
                  </li>
                )
              })}
            </ul>
          )}
        </section>
      </CardBody>
    </Card>
  )
}
