import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { AxiosError } from 'axios'
import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { toast } from 'sonner'
import { useIdempotencyKey } from '@/shared/api/idempotency'
import { Button } from '@/shared/components/Button'
import { Card, CardBody, CardHeader } from '@/shared/components/Card'
import {
  cancelPurchaseOrder,
  getPurchaseOrder,
  resubmitPurchaseOrder,
  submitPurchaseOrder,
} from './api'
import { StatusBadge } from './components/StatusBadge'
import {
  canResubmit,
  canSubmit,
  isCancellable,
  isEditable,
  type PurchaseOrder,
} from './types'

interface ApiErrorBody {
  error?: string
  message?: string
}

function showApiError(e: unknown, fallback: string): void {
  const err = e as AxiosError<ApiErrorBody>
  toast.error(err.response?.data?.message ?? fallback)
}

function formatCurrency(n: number): string {
  return n.toLocaleString(undefined, { style: 'currency', currency: 'USD' })
}

function formatDate(iso: string | null): string {
  if (iso === null) return '—'
  return new Date(iso).toLocaleString()
}

export function PurchaseOrderDetailPage() {
  const { id = '' } = useParams<{ id: string }>()

  const { data: po, isLoading, isError } = useQuery({
    queryKey: ['purchase-order', id],
    queryFn: () => getPurchaseOrder(id),
  })

  if (isLoading) return <div className="p-8 text-sm text-gray-500">Loading…</div>
  if (isError || po === undefined) {
    return <div className="p-8 text-sm text-red-600">Couldn't load purchase order.</div>
  }

  return <PurchaseOrderDetail po={po} />
}

function PurchaseOrderDetail({ po }: { po: PurchaseOrder }) {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const submitKey = useIdempotencyKey()
  const resubmitKey = useIdempotencyKey()
  const cancelKey = useIdempotencyKey()
  const [showCancel, setShowCancel] = useState(false)
  const [cancelReason, setCancelReason] = useState('')

  const invalidate = async () => {
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: ['purchase-order', String(po.id)] }),
      queryClient.invalidateQueries({ queryKey: ['purchase-orders'] }),
      queryClient.invalidateQueries({ queryKey: ['inbox'] }),
    ])
  }

  const submitMutation = useMutation({
    mutationFn: () => submitPurchaseOrder(po.id, submitKey.key),
    onSuccess: async () => {
      submitKey.rotate()
      await invalidate()
      toast.success('Submitted for approval')
    },
    onError: (e) => {
      showApiError(e, 'Submit failed')
    },
  })

  const resubmitMutation = useMutation({
    mutationFn: () => resubmitPurchaseOrder(po.id, resubmitKey.key),
    onSuccess: async () => {
      resubmitKey.rotate()
      await invalidate()
      toast.success('Resubmitted')
    },
    onError: (e) => {
      showApiError(e, 'Resubmit failed')
    },
  })

  const cancelMutation = useMutation({
    mutationFn: () =>
      cancelPurchaseOrder(po.id, cancelKey.key, cancelReason.trim() || undefined),
    onSuccess: async () => {
      cancelKey.rotate()
      setShowCancel(false)
      setCancelReason('')
      await invalidate()
      toast.success('Cancelled')
    },
    onError: (e) => {
      showApiError(e, 'Cancel failed')
    },
  })

  return (
    <div className="mx-auto max-w-4xl space-y-4 p-6">
      <div className="flex items-center justify-between">
        <Link to="/purchase-orders" className="text-sm text-blue-600 hover:underline">
          ← All purchase orders
        </Link>
        <div className="flex gap-2">
          {isEditable(po.status) ? (
            <Button
              variant="secondary"
              size="sm"
              onClick={() => navigate(`/purchase-orders/${String(po.id)}/edit`)}
            >
              Edit
            </Button>
          ) : null}
          {canSubmit(po.status) ? (
            <Button
              size="sm"
              disabled={submitMutation.isPending}
              onClick={() => submitMutation.mutate()}
            >
              {submitMutation.isPending ? 'Submitting…' : 'Submit for approval'}
            </Button>
          ) : null}
          {canResubmit(po.status) ? (
            <Button
              size="sm"
              disabled={resubmitMutation.isPending}
              onClick={() => resubmitMutation.mutate()}
            >
              {resubmitMutation.isPending ? 'Resubmitting…' : 'Resubmit'}
            </Button>
          ) : null}
          {isCancellable(po.status) ? (
            <Button variant="danger" size="sm" onClick={() => setShowCancel(true)}>
              Cancel
            </Button>
          ) : null}
        </div>
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-start justify-between gap-4">
            <div>
              <h1 className="text-lg font-semibold text-gray-900">{po.title}</h1>
              {po.description !== null && po.description !== '' ? (
                <p className="mt-1 text-sm text-gray-600">{po.description}</p>
              ) : null}
            </div>
            <StatusBadge status={po.status} />
          </div>
        </CardHeader>
        <CardBody>
          <dl className="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
            <div>
              <dt className="text-xs uppercase tracking-wide text-gray-500">Amount</dt>
              <dd className="font-semibold text-gray-900">{formatCurrency(po.amount)}</dd>
            </div>
            <div>
              <dt className="text-xs uppercase tracking-wide text-gray-500">Category</dt>
              <dd className="text-gray-900">{po.category}</dd>
            </div>
            <div>
              <dt className="text-xs uppercase tracking-wide text-gray-500">Submissions</dt>
              <dd className="text-gray-900">{po.submission_count}</dd>
            </div>
            <div>
              <dt className="text-xs uppercase tracking-wide text-gray-500">Submitted</dt>
              <dd className="text-gray-900">{formatDate(po.submitted_at)}</dd>
            </div>
            {po.requester !== undefined ? (
              <div>
                <dt className="text-xs uppercase tracking-wide text-gray-500">Requester</dt>
                <dd className="text-gray-900">{po.requester.name}</dd>
              </div>
            ) : null}
            <div>
              <dt className="text-xs uppercase tracking-wide text-gray-500">Approved</dt>
              <dd className="text-gray-900">{formatDate(po.approved_at)}</dd>
            </div>
            <div>
              <dt className="text-xs uppercase tracking-wide text-gray-500">Rejected</dt>
              <dd className="text-gray-900">{formatDate(po.rejected_at)}</dd>
            </div>
            <div>
              <dt className="text-xs uppercase tracking-wide text-gray-500">Cancelled</dt>
              <dd className="text-gray-900">{formatDate(po.cancelled_at)}</dd>
            </div>
          </dl>

          {po.last_rejection_reason !== null ? (
            <div className="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
              <span className="font-semibold">Last rejection reason:</span>{' '}
              {po.last_rejection_reason}
            </div>
          ) : null}
        </CardBody>
      </Card>

      <Card>
        <CardHeader>
          <h2 className="text-sm font-semibold text-gray-900">Line items</h2>
        </CardHeader>
        <table className="w-full text-sm">
          <thead className="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500">
            <tr>
              <th className="px-4 py-2 text-left">Name</th>
              <th className="px-4 py-2 text-right">Qty</th>
              <th className="px-4 py-2 text-right">Unit price</th>
              <th className="px-4 py-2 text-right">Line total</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {(po.items ?? []).map((item) => (
              <tr key={item.id}>
                <td className="px-4 py-2 text-gray-900">{item.name}</td>
                <td className="px-4 py-2 text-right tabular-nums text-gray-700">{item.quantity}</td>
                <td className="px-4 py-2 text-right tabular-nums text-gray-700">
                  {formatCurrency(item.unit_price)}
                </td>
                <td className="px-4 py-2 text-right tabular-nums text-gray-900">
                  {formatCurrency(item.line_total)}
                </td>
              </tr>
            ))}
          </tbody>
          <tfoot>
            <tr className="bg-gray-50">
              <td className="px-4 py-2 text-right font-medium" colSpan={3}>
                Total
              </td>
              <td className="px-4 py-2 text-right font-semibold text-gray-900 tabular-nums">
                {formatCurrency(po.amount)}
              </td>
            </tr>
          </tfoot>
        </table>
      </Card>

      {showCancel ? (
        <Card>
          <CardHeader>
            <h2 className="text-sm font-semibold text-gray-900">Cancel purchase order</h2>
          </CardHeader>
          <CardBody>
            <p className="text-sm text-gray-700">
              Optional reason (will be recorded in the audit log).
            </p>
            <textarea
              value={cancelReason}
              onChange={(e) => setCancelReason(e.target.value)}
              rows={2}
              className="mt-2 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
              placeholder="Reason for cancellation"
            />
            <div className="mt-3 flex justify-end gap-2">
              <Button variant="ghost" size="sm" onClick={() => setShowCancel(false)}>
                Keep
              </Button>
              <Button
                variant="danger"
                size="sm"
                disabled={cancelMutation.isPending}
                onClick={() => cancelMutation.mutate()}
              >
                {cancelMutation.isPending ? 'Cancelling…' : 'Cancel PO'}
              </Button>
            </div>
          </CardBody>
        </Card>
      ) : null}
    </div>
  )
}
