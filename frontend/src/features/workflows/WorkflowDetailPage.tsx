import { useQuery } from '@tanstack/react-query'
import { ChevronRight, Plus } from 'lucide-react'
import { Link, useParams } from 'react-router-dom'
import { Badge } from '@/shared/components/Badge'
import { Button } from '@/shared/components/Button'
import { Card, CardBody, CardHeader } from '@/shared/components/Card'
import { Skeleton } from '@/shared/components/Skeleton'
import { formatDate } from '@/shared/utils/format'
import { getWorkflow } from './api'

export function WorkflowDetailPage() {
  const { id = '' } = useParams<{ id: string }>()

  const { data: workflow, isLoading, isError } = useQuery({
    queryKey: ['workflow', id],
    queryFn: () => getWorkflow(id),
  })

  if (isLoading) {
    return (
      <div className="mx-auto max-w-4xl space-y-3 p-6">
        <Skeleton className="h-6 w-1/3" />
        <Skeleton className="h-20 w-full" />
        <Skeleton className="h-20 w-full" />
      </div>
    )
  }
  if (isError || workflow === undefined) {
    return <div className="p-8 text-sm text-red-600">Workflow not found.</div>
  }

  const versions = (workflow.versions ?? []).slice().sort((a, b) => b.version_number - a.version_number)
  const latestPublished = versions.find((v) => v.is_published)
  const cloneParam = latestPublished !== undefined ? `?cloneFrom=${String(latestPublished.id)}` : ''

  return (
    <div className="mx-auto max-w-4xl space-y-4 p-6">
      <Link to="/admin/workflows" className="text-sm text-brand-600 hover:underline">
        ← All workflows
      </Link>

      <Card>
        <CardHeader>
          <div className="flex items-start justify-between gap-3">
            <div>
              <h1 className="text-lg font-semibold text-gray-900">{workflow.name}</h1>
              <p className="mt-1 text-xs text-gray-500">
                subject: <code className="rounded bg-gray-100 px-1">{workflow.subject_type}</code>
              </p>
            </div>
            {workflow.is_active ? <Badge tone="green">active</Badge> : <Badge tone="gray">inactive</Badge>}
          </div>
        </CardHeader>
      </Card>

      <div className="flex items-center justify-between">
        <h2 className="text-sm font-semibold text-gray-900">Versions</h2>
        <Link to={`/admin/workflows/${String(workflow.id)}/versions/new${cloneParam}`}>
          <Button size="sm">
            <Plus size={16} /> New version
          </Button>
        </Link>
      </div>

      {versions.length === 0 ? (
        <Card>
          <CardBody>
            <p className="text-sm text-gray-500">
              No versions yet. Create one to define the steps.
            </p>
          </CardBody>
        </Card>
      ) : (
        <ul className="space-y-2">
          {versions.map((v) => (
            <li key={v.id}>
              <Link to={`/admin/workflow-versions/${String(v.id)}`} className="group block">
                <Card className="transition-all hover:border-brand-200 hover:shadow-md">
                  <CardBody className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                      <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 text-sm font-semibold text-gray-700">
                        v{v.version_number}
                      </span>
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="text-sm font-medium text-gray-900">
                            Version {v.version_number}
                          </span>
                          {v.is_published ? (
                            <Badge tone="green">published</Badge>
                          ) : (
                            <Badge tone="amber">draft</Badge>
                          )}
                        </div>
                        <p className="text-xs text-gray-500">
                          {v.is_published && v.published_at !== null
                            ? `Published ${formatDate(v.published_at)}`
                            : 'Not yet published'}
                        </p>
                      </div>
                    </div>
                    <ChevronRight
                      size={16}
                      className="text-gray-400 transition-transform group-hover:translate-x-0.5 group-hover:text-brand-500"
                    />
                  </CardBody>
                </Card>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
