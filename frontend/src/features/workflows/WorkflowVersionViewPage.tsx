import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { AxiosError } from 'axios'
import { Send } from 'lucide-react'
import { Link, useParams } from 'react-router-dom'
import { toast } from 'sonner'
import { Badge } from '@/shared/components/Badge'
import { Button } from '@/shared/components/Button'
import { Card, CardBody, CardHeader } from '@/shared/components/Card'
import { Skeleton } from '@/shared/components/Skeleton'
import { formatDate } from '@/shared/utils/format'
import { getWorkflowVersion, publishWorkflowVersion } from './api'
import { ApproverPill } from './components/ApproverPill'
import { ConditionPill } from './components/ConditionPill'

interface ApiErrorBody {
  message?: string
}

const modeLabel: Record<string, string> = {
  single: 'single',
  parallel_any: 'parallel any',
  parallel_all: 'parallel all',
  quorum: 'quorum',
}

export function WorkflowVersionViewPage() {
  const { id = '' } = useParams<{ id: string }>()
  const queryClient = useQueryClient()

  const { data: version, isLoading, isError } = useQuery({
    queryKey: ['workflow-version', id],
    queryFn: () => getWorkflowVersion(id),
  })

  const publish = useMutation({
    mutationFn: () => publishWorkflowVersion(id),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['workflow-version', id] })
      await queryClient.invalidateQueries({
        queryKey: ['workflow', String(version?.workflow_id ?? '')],
      })
      await queryClient.invalidateQueries({ queryKey: ['workflows'] })
      toast.success('Version published — new processes will use this version')
    },
    onError: (e) => {
      const err = e as AxiosError<ApiErrorBody>
      toast.error(err.response?.data?.message ?? 'Publish failed')
    },
  })

  if (isLoading) {
    return (
      <div className="mx-auto max-w-4xl space-y-3 p-6">
        <Skeleton className="h-6 w-1/3" />
        <Skeleton className="h-32 w-full" />
        <Skeleton className="h-32 w-full" />
      </div>
    )
  }
  if (isError || version === undefined) {
    return <div className="p-8 text-sm text-red-600">Version not found.</div>
  }

  const steps = (version.steps ?? []).slice().sort((a, b) => a.order - b.order)

  return (
    <div className="mx-auto max-w-4xl space-y-4 p-6">
      <div className="flex items-center justify-between">
        <Link
          to={`/admin/workflows/${String(version.workflow_id)}`}
          className="text-sm text-brand-600 hover:underline"
        >
          ← Back to workflow
        </Link>
        <div className="flex items-center gap-2">
          {version.is_published ? (
            <Link to={`/admin/workflows/${String(version.workflow_id)}/versions/new?cloneFrom=${String(version.id)}`}>
              <Button variant="secondary" size="sm">
                Clone to new draft
              </Button>
            </Link>
          ) : (
            <Button
              size="sm"
              disabled={publish.isPending}
              onClick={() => publish.mutate()}
            >
              <Send size={14} />
              {publish.isPending ? 'Publishing…' : 'Publish'}
            </Button>
          )}
        </div>
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-start justify-between gap-3">
            <div>
              <h1 className="text-lg font-semibold text-gray-900">
                Version {version.version_number}
              </h1>
              <p className="mt-1 text-xs text-gray-500">
                {version.is_published && version.published_at !== null
                  ? `Published ${formatDate(version.published_at)}`
                  : 'Draft — not yet published'}
              </p>
            </div>
            {version.is_published ? (
              <Badge tone="green">published</Badge>
            ) : (
              <Badge tone="amber">draft</Badge>
            )}
          </div>
        </CardHeader>
        <CardBody>
          <p className="text-xs text-gray-500">
            {version.is_published
              ? 'Published versions are immutable. Clone to a new draft to make changes.'
              : 'Drafts can still be edited. Publishing makes the version available for new processes (existing in-flight processes stay on their pinned version).'}
          </p>
        </CardBody>
      </Card>

      <ol className="space-y-3">
        {steps.map((step) => (
          <li key={step.id}>
            <Card>
              <CardBody>
                <div className="flex items-start justify-between gap-3">
                  <div className="flex items-start gap-3">
                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                      {step.order}
                    </span>
                    <div>
                      <h3 className="text-sm font-semibold text-gray-900">{step.name}</h3>
                      <p className="text-xs text-gray-500">
                        Mode: {modeLabel[step.approval_mode] ?? step.approval_mode}
                        {step.approval_mode === 'quorum'
                          ? ` · ${step.required_approvals} of N required`
                          : ''}
                      </p>
                    </div>
                  </div>
                </div>

                <div className="mt-4 space-y-3">
                  <div>
                    <h4 className="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                      Conditions
                    </h4>
                    {(step.conditions ?? []).length === 0 ? (
                      <span className="text-xs italic text-gray-500">always (no condition)</span>
                    ) : (
                      <div className="flex flex-wrap gap-1.5">
                        {(step.conditions ?? []).map((c) => (
                          <ConditionPill key={c.id} condition={c} />
                        ))}
                      </div>
                    )}
                  </div>
                  <div>
                    <h4 className="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                      Approvers
                    </h4>
                    <div className="flex flex-wrap gap-1.5">
                      {(step.approvers ?? []).map((a) => (
                        <ApproverPill key={a.id} approver={a} />
                      ))}
                    </div>
                  </div>
                </div>
              </CardBody>
            </Card>
          </li>
        ))}
      </ol>
    </div>
  )
}
