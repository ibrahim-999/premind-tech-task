import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { AxiosError } from 'axios'
import { Plus, Save } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import { toast } from 'sonner'
import { Badge } from '@/shared/components/Badge'
import { Button } from '@/shared/components/Button'
import { Card, CardHeader } from '@/shared/components/Card'
import { createWorkflowVersion, getWorkflow, getWorkflowVersion } from './api'
import { StepEditor, type EditorStep } from './components/StepEditor'

interface ApiErrorBody {
  message?: string
  details?: Record<string, string[]>
}

function showApiError(e: unknown, fallback: string): void {
  const err = e as AxiosError<ApiErrorBody>
  const detail = err.response?.data?.details
  if (detail !== undefined) {
    const first = Object.values(detail)[0]?.[0]
    if (first !== undefined) {
      toast.error(first)
      return
    }
  }
  toast.error(err.response?.data?.message ?? fallback)
}

function emptyStep(): EditorStep {
  return {
    id: crypto.randomUUID(),
    name: '',
    approval_mode: 'single',
    required_approvals: 1,
    conditions: [],
    approvers: [],
  }
}

export function WorkflowVersionEditPage() {
  const { workflowId = '' } = useParams<{ workflowId: string }>()
  const [searchParams] = useSearchParams()
  const cloneFromVersionId = searchParams.get('cloneFrom')

  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const { data: workflow, isLoading: loadingWorkflow } = useQuery({
    queryKey: ['workflow', workflowId],
    queryFn: () => getWorkflow(workflowId),
  })

  const { data: cloneSource, isLoading: loadingClone } = useQuery({
    queryKey: ['workflow-version', cloneFromVersionId],
    queryFn: () => getWorkflowVersion(cloneFromVersionId as string),
    enabled: cloneFromVersionId !== null,
  })

  const [steps, setSteps] = useState<EditorStep[] | null>(null)

  useEffect(() => {
    if (steps !== null) return
    if (cloneFromVersionId !== null) {
      if (cloneSource !== undefined) {
        setSteps(
          (cloneSource.steps ?? []).map((s) => ({
            id: crypto.randomUUID(),
            name: s.name,
            approval_mode: s.approval_mode,
            required_approvals: s.required_approvals,
            conditions: (s.conditions ?? []).map((c) => ({
              id: crypto.randomUUID(),
              type: c.type,
              config: { ...c.config },
            })),
            approvers: (s.approvers ?? []).map((a) => ({
              id: crypto.randomUUID(),
              resolver_type: a.resolver_type,
              config: { ...a.config },
            })),
          })),
        )
      }
    } else {
      setSteps([emptyStep()])
    }
  }, [cloneFromVersionId, cloneSource, steps])

  const mutation = useMutation({
    mutationFn: () => {
      if (steps === null) throw new Error('steps not initialised')
      return createWorkflowVersion(workflowId, {
        steps: steps.map((s) => ({
          name: s.name,
          approval_mode: s.approval_mode,
          required_approvals: s.required_approvals,
          conditions: s.conditions.map((c) => ({ type: c.type, config: c.config })),
          approvers: s.approvers.map((a) => ({
            resolver_type: a.resolver_type,
            config: a.config,
          })),
        })),
      })
    },
    onSuccess: async (version) => {
      await queryClient.invalidateQueries({ queryKey: ['workflows'] })
      await queryClient.invalidateQueries({ queryKey: ['workflow', workflowId] })
      toast.success(`Draft v${String(version.version_number)} saved`)
      navigate(`/admin/workflow-versions/${String(version.id)}`)
    },
    onError: (e) => {
      showApiError(e, 'Save failed')
    },
  })

  if (loadingWorkflow || (cloneFromVersionId !== null && loadingClone) || steps === null) {
    return <div className="p-8 text-sm text-gray-500">Loading…</div>
  }

  if (workflow === undefined) {
    return <div className="p-8 text-sm text-red-600">Workflow not found.</div>
  }

  const validate = (): string | null => {
    if (steps.length === 0) return 'Add at least one step'
    for (const [i, step] of steps.entries()) {
      if (step.name.trim() === '') return `Step ${String(i + 1)} needs a name`
      if (step.approvers.length === 0)
        return `Step ${String(i + 1)} needs at least one approver`
      if (step.approval_mode === 'quorum' && step.required_approvals < 1)
        return `Step ${String(i + 1)} quorum must be ≥ 1`
    }
    return null
  }

  const onSave = () => {
    const err = validate()
    if (err !== null) {
      toast.error(err)
      return
    }
    mutation.mutate()
  }

  return (
    <div className="mx-auto max-w-4xl space-y-4 p-6">
      <div className="flex items-center justify-between">
        <Link
          to={`/admin/workflows/${workflowId}`}
          className="text-sm text-brand-600 hover:underline"
        >
          ← {workflow.name}
        </Link>
        <Badge tone="amber">draft</Badge>
      </div>

      <Card>
        <CardHeader>
          <h1 className="text-lg font-semibold text-gray-900">
            {cloneFromVersionId !== null ? 'Edit new draft' : 'New workflow version'}
          </h1>
          <p className="mt-1 text-sm text-gray-600">
            Drag-free reorder with the up / down arrows. The save button creates this as an
            unpublished draft. Publish from the version page once it looks right.
          </p>
        </CardHeader>
      </Card>

      <ol className="space-y-4">
        {steps.map((step, i) => (
          <li key={step.id}>
            <StepEditor
              step={step}
              index={i}
              isFirst={i === 0}
              isLast={i === steps.length - 1}
              onChange={(next) => {
                setSteps(steps.map((s, j) => (j === i ? next : s)))
              }}
              onRemove={() => {
                setSteps(steps.filter((_, j) => j !== i))
              }}
              onMoveUp={() => {
                if (i === 0) return
                const next = [...steps]
                ;[next[i - 1], next[i]] = [next[i]!, next[i - 1]!]
                setSteps(next)
              }}
              onMoveDown={() => {
                if (i === steps.length - 1) return
                const next = [...steps]
                ;[next[i], next[i + 1]] = [next[i + 1]!, next[i]!]
                setSteps(next)
              }}
            />
          </li>
        ))}
      </ol>

      <div className="flex items-center justify-between">
        <Button
          type="button"
          variant="secondary"
          onClick={() => setSteps([...steps, emptyStep()])}
        >
          <Plus size={16} /> Add step
        </Button>
        <Button type="button" disabled={mutation.isPending} onClick={onSave}>
          <Save size={16} /> {mutation.isPending ? 'Saving…' : 'Save draft'}
        </Button>
      </div>
    </div>
  )
}
