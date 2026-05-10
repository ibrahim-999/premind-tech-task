import { useQuery } from '@tanstack/react-query'
import { Plus } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Button } from '@/shared/components/Button'
import { Card } from '@/shared/components/Card'
import { listPurchaseOrders } from './api'
import { StatusBadge } from './components/StatusBadge'

function formatCurrency(n: number): string {
  return n.toLocaleString(undefined, { style: 'currency', currency: 'USD' })
}

function formatDate(iso: string | null): string {
  if (iso === null) return '—'
  return new Date(iso).toLocaleString()
}

export function PurchaseOrderListPage() {
  const { data, isLoading, isError } = useQuery({
    queryKey: ['purchase-orders'],
    queryFn: listPurchaseOrders,
  })

  return (
    <div className="mx-auto max-w-7xl p-6">
      <div className="mb-4 flex items-center justify-between">
        <h1 className="text-xl font-semibold text-gray-900">Purchase Orders</h1>
        <Link to="/purchase-orders/new">
          <Button size="sm">
            <Plus size={16} /> New PO
          </Button>
        </Link>
      </div>

      <Card>
        {isLoading ? (
          <div className="px-4 py-12 text-center text-sm text-gray-500">Loading…</div>
        ) : isError ? (
          <div className="px-4 py-12 text-center text-sm text-red-600">Couldn't load purchase orders.</div>
        ) : (data?.data ?? []).length === 0 ? (
          <div className="px-4 py-12 text-center text-sm text-gray-500">
            No purchase orders yet.
          </div>
        ) : (
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500">
              <tr>
                <th className="px-4 py-2 text-left">Title</th>
                <th className="px-4 py-2 text-left">Category</th>
                <th className="px-4 py-2 text-right">Amount</th>
                <th className="px-4 py-2 text-left">Status</th>
                <th className="px-4 py-2 text-left">Submitted</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {(data?.data ?? []).map((po) => (
                <tr key={po.id} className="hover:bg-gray-50">
                  <td className="px-4 py-2">
                    <Link
                      to={`/purchase-orders/${String(po.id)}`}
                      className="font-medium text-blue-600 hover:underline"
                    >
                      {po.title}
                    </Link>
                  </td>
                  <td className="px-4 py-2 text-gray-700">{po.category}</td>
                  <td className="px-4 py-2 text-right tabular-nums text-gray-900">
                    {formatCurrency(po.amount)}
                  </td>
                  <td className="px-4 py-2">
                    <StatusBadge status={po.status} />
                  </td>
                  <td className="px-4 py-2 text-gray-500">{formatDate(po.submitted_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>
    </div>
  )
}
