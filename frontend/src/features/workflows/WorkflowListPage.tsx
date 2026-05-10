import { useQuery } from '@tanstack/react-query'
import { ChevronRight, Plus, Workflow as WorkflowIcon } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Badge } from '@/shared/components/Badge'
import { Button } from '@/shared/components/Button'
import { Card, CardBody } from '@/shared/components/Card'
import { EmptyState } from '@/shared/components/EmptyState'
import { Skeleton } from '@/shared/components/Skeleton'
import { listWorkflows } from './api'

export function WorkflowListPage() {
  const { data, isLoading, isError } = useQuery({
    queryKey: ['workflows'],
    queryFn: listWorkflows,
  })

  return (
    <div className="mx-auto max-w-5xl p-6">
      <header className="mb-6 flex items-baseline justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-gray-900">Workflows</h1>
          <p className="mt-1 text-sm text-gray-600">
            Active approval workflows by subject type. Click into a workflow to see its versions and steps.
          </p>
        </div>
        <Link to="/admin/workflows/new">
          <Button size="sm">
            <Plus size={16} /> New workflow
          </Button>
        </Link>
      </header>

      {isLoading ? (
        <ul className="space-y-3">
          {Array.from({ length: 2 }).map((_, i) => (
            <li key={i}>
              <Card>
                <CardBody>
                  <Skeleton className="h-4 w-1/3" />
                  <Skeleton className="mt-2 h-3 w-1/2" />
                </CardBody>
              </Card>
            </li>
          ))}
        </ul>
      ) : isError ? (
        <Card>
          <CardBody>
            <p className="text-sm text-red-600">Couldn't load workflows.</p>
          </CardBody>
        </Card>
      ) : (data?.data ?? []).length === 0 ? (
        <Card>
          <EmptyState
            icon={<WorkflowIcon size={20} />}
            title="No workflows configured"
            description="Run the seeder or create a workflow via the API."
          />
        </Card>
      ) : (
        <ul className="space-y-3">
          {(data?.data ?? []).map((workflow) => {
            const versions = workflow.versions ?? []
            const published = versions.filter((v) => v.is_published).length
            return (
              <li key={workflow.id}>
                <Link to={`/admin/workflows/${String(workflow.id)}`} className="group block">
                  <Card className="transition-all hover:border-brand-200 hover:shadow-md">
                    <CardBody className="flex items-center gap-4">
                      <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                        <WorkflowIcon size={18} />
                      </div>
                      <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                          <h2 className="truncate text-sm font-semibold text-gray-900">
                            {workflow.name}
                          </h2>
                          {workflow.is_active ? (
                            <Badge tone="green">active</Badge>
                          ) : (
                            <Badge tone="gray">inactive</Badge>
                          )}
                        </div>
                        <p className="mt-0.5 text-xs text-gray-500">
                          subject: <code className="rounded bg-gray-100 px-1">{workflow.subject_type}</code>
                          <span className="mx-1.5 text-gray-300">·</span>
                          {versions.length} version{versions.length === 1 ? '' : 's'}
                          <span className="mx-1.5 text-gray-300">·</span>
                          {published} published
                        </p>
                      </div>
                      <ChevronRight
                        size={16}
                        className="text-gray-400 transition-transform group-hover:translate-x-0.5 group-hover:text-brand-500"
                      />
                    </CardBody>
                  </Card>
                </Link>
              </li>
            )
          })}
        </ul>
      )}
    </div>
  )
}
