import { useQuery } from '@tanstack/react-query'
import { ArrowRight, Inbox } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Avatar } from '@/shared/components/Avatar'
import { Badge } from '@/shared/components/Badge'
import { Card, CardBody } from '@/shared/components/Card'
import { EmptyState } from '@/shared/components/EmptyState'
import { Skeleton } from '@/shared/components/Skeleton'
import { formatCurrency, formatRelative } from '@/shared/utils/format'
import { getInbox } from './api'
import type { InboxItem } from './types'

export function InboxPage() {
  const { data, isLoading, isError } = useQuery({
    queryKey: ['inbox'],
    queryFn: () => getInbox(),
    refetchOnWindowFocus: true,
  })

  return (
    <div className="mx-auto max-w-5xl p-6">
      <header className="mb-6 flex items-baseline justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-gray-900">Inbox</h1>
          <p className="mt-1 text-sm text-gray-600">Approvals waiting on you.</p>
        </div>
        {data !== undefined && data.data.length > 0 ? (
          <Badge tone="amber">{data.data.length} pending</Badge>
        ) : null}
      </header>

      {isLoading ? (
        <ul className="space-y-3">
          {Array.from({ length: 3 }).map((_, i) => (
            <li key={i}>
              <Card>
                <CardBody className="flex items-center gap-4">
                  <Skeleton className="h-9 w-9 rounded-full" />
                  <div className="flex-1 space-y-2">
                    <Skeleton className="h-3 w-1/2" />
                    <Skeleton className="h-3 w-1/3" />
                  </div>
                  <Skeleton className="h-5 w-20" />
                </CardBody>
              </Card>
            </li>
          ))}
        </ul>
      ) : isError ? (
        <Card>
          <CardBody>
            <p className="text-sm text-red-600">Couldn't load your inbox.</p>
          </CardBody>
        </Card>
      ) : (data?.data ?? []).length === 0 ? (
        <Card>
          <EmptyState
            icon={<Inbox size={20} />}
            title="Nothing pending your approval"
            description="You're all caught up. New approval requests will appear here."
          />
        </Card>
      ) : (
        <ul className="space-y-3">
          {(data?.data ?? []).map((item) => (
            <InboxRow key={item.step_instance_id} item={item} />
          ))}
        </ul>
      )}
    </div>
  )
}

function InboxRow({ item }: { item: InboxItem }) {
  const submitterName = item.submitted_by?.name ?? 'unknown'

  return (
    <li>
      <Link to={`/processes/${String(item.process_id)}`} className="group block">
        <Card className="transition-all hover:border-brand-200 hover:shadow-md">
          <CardBody>
            <div className="flex items-center gap-4">
              <Avatar name={submitterName} size="md" />
              <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                  <h2 className="truncate text-sm font-semibold text-gray-900">
                    {item.subject?.title ?? 'Untitled'}
                  </h2>
                  <Badge tone="amber">{item.step_name}</Badge>
                  {item.subject?.category !== undefined ? (
                    <Badge tone="gray">{item.subject.category}</Badge>
                  ) : null}
                </div>
                <p className="mt-1 text-xs text-gray-500">
                  from <span className="font-medium text-gray-700">{submitterName}</span>
                  {item.submitted_at !== null ? (
                    <span> · submitted {formatRelative(item.submitted_at)}</span>
                  ) : null}
                </p>
              </div>
              <div className="flex flex-col items-end gap-1">
                <span className="text-base font-semibold tabular-nums text-gray-900">
                  {formatCurrency(item.subject?.amount)}
                </span>
                <span className="inline-flex items-center gap-1 text-xs font-medium text-brand-600 transition-transform group-hover:translate-x-0.5">
                  Review <ArrowRight size={12} />
                </span>
              </div>
            </div>
          </CardBody>
        </Card>
      </Link>
    </li>
  )
}
