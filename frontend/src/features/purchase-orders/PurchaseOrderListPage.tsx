import { useQuery } from '@tanstack/react-query'
import { FileText, Plus } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Button } from '@/shared/components/Button'
import { Card } from '@/shared/components/Card'
import { EmptyState } from '@/shared/components/EmptyState'
import { Skeleton } from '@/shared/components/Skeleton'
import { formatCurrency, formatRelative } from '@/shared/utils/format'
import { listPurchaseOrders } from './api'
import { StatusBadge } from './components/StatusBadge'

export function PurchaseOrderListPage() {
  const { data, isLoading, isError } = useQuery({
    queryKey: ['purchase-orders'],
    queryFn: listPurchaseOrders,
  })

  return (
    <div className="mx-auto max-w-7xl p-6">
      <header className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-gray-900">
            Purchase Orders
          </h1>
          <p className="mt-1 text-sm text-gray-600">
            Drafts you've created and the ones you've already submitted.
          </p>
        </div>
        <Link to="/purchase-orders/new">
          <Button size="sm">
            <Plus size={16} /> New PO
          </Button>
        </Link>
      </header>

      <Card>
        {isLoading ? (
          <div className="space-y-2 p-4">
            {Array.from({ length: 4 }).map((_, i) => (
              <div key={i} className="flex items-center gap-4 py-2">
                <Skeleton className="h-3 w-1/3" />
                <Skeleton className="h-3 w-20" />
                <Skeleton className="ml-auto h-3 w-24" />
                <Skeleton className="h-5 w-20 rounded-full" />
              </div>
            ))}
          </div>
        ) : isError ? (
          <div className="px-4 py-12 text-center text-sm text-red-600">
            Couldn't load purchase orders.
          </div>
        ) : (data?.data ?? []).length === 0 ? (
          <EmptyState
            icon={<FileText size={20} />}
            title="No purchase orders yet"
            description="Create your first PO to start the approval flow."
            action={
              <Link to="/purchase-orders/new">
                <Button size="sm">
                  <Plus size={16} /> New PO
                </Button>
              </Link>
            }
          />
        ) : (
          <table className="w-full text-sm">
            <thead className="border-b border-gray-200 bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500">
              <tr>
                <th className="px-4 py-2.5 text-left">Title</th>
                <th className="px-4 py-2.5 text-left">Category</th>
                <th className="px-4 py-2.5 text-right">Amount</th>
                <th className="px-4 py-2.5 text-left">Status</th>
                <th className="px-4 py-2.5 text-left">Submitted</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {(data?.data ?? []).map((po) => (
                <tr key={po.id} className="transition-colors hover:bg-gray-50">
                  <td className="px-4 py-3">
                    <Link
                      to={`/purchase-orders/${String(po.id)}`}
                      className="font-medium text-gray-900 hover:text-brand-700"
                    >
                      {po.title}
                    </Link>
                  </td>
                  <td className="px-4 py-3 text-gray-700">{po.category}</td>
                  <td className="px-4 py-3 text-right tabular-nums text-gray-900">
                    {formatCurrency(po.amount)}
                  </td>
                  <td className="px-4 py-3">
                    <StatusBadge status={po.status} />
                  </td>
                  <td className="px-4 py-3 text-gray-500">
                    {po.submitted_at !== null ? formatRelative(po.submitted_at) : '—'}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>
    </div>
  )
}
