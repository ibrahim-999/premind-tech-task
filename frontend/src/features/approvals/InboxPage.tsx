import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Badge } from '@/shared/components/Badge'
import { Card, CardBody } from '@/shared/components/Card'
import { getInbox } from './api'
import type { InboxItem } from './types'

function formatCurrency(n: number | undefined): string {
  if (n === undefined) return '—'
  return n.toLocaleString(undefined, { style: 'currency', currency: 'USD' })
}

function formatDate(iso: string | null): string {
  if (iso === null) return ''
  return new Date(iso).toLocaleString()
}

export function InboxPage() {
  const { data, isLoading, isError } = useQuery({
    queryKey: ['inbox'],
    queryFn: () => getInbox(),
    refetchOnWindowFocus: true,
  })

  return (
    <div className="mx-auto max-w-5xl p-6">
      <div className="mb-4 flex items-baseline justify-between">
        <h1 className="text-xl font-semibold text-gray-900">Inbox</h1>
        <p className="text-sm text-gray-600">
          Approvals waiting on you.
        </p>
      </div>

      {isLoading ? (
        <Card>
          <CardBody>
            <p className="text-sm text-gray-500">Loading…</p>
          </CardBody>
        </Card>
      ) : isError ? (
        <Card>
          <CardBody>
            <p className="text-sm text-red-600">Couldn't load your inbox.</p>
          </CardBody>
        </Card>
      ) : (data?.data ?? []).length === 0 ? (
        <Card>
          <CardBody>
            <p className="text-sm text-gray-500">
              Nothing pending your approval right now.
            </p>
          </CardBody>
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
  return (
    <li>
      <Link to={`/processes/${String(item.process_id)}`} className="block">
        <Card className="transition-shadow hover:shadow-md">
          <CardBody>
            <div className="flex items-start justify-between gap-4">
              <div className="min-w-0">
                <div className="flex items-center gap-2">
                  <h2 className="truncate text-sm font-semibold text-gray-900">
                    {item.subject?.title ?? 'Untitled'}
                  </h2>
                  <Badge tone="amber">{item.step_name}</Badge>
                </div>
                <p className="mt-1 text-xs text-gray-500">
                  {item.subject?.category !== undefined ? `${item.subject.category} · ` : ''}
                  {item.submitted_by !== null ? `from ${item.submitted_by.name}` : ''}
                  {item.submitted_at !== null ? ` · submitted ${formatDate(item.submitted_at)}` : ''}
                </p>
              </div>
              <div className="text-right">
                <div className="text-base font-semibold text-gray-900 tabular-nums">
                  {formatCurrency(item.subject?.amount)}
                </div>
                <div className="mt-1 text-xs text-blue-600">Review →</div>
              </div>
            </div>
          </CardBody>
        </Card>
      </Link>
    </li>
  )
}
