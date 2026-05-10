import { useQuery } from '@tanstack/react-query'
import { Link, useParams } from 'react-router-dom'
import { useAuth } from '@/features/auth/AuthProvider'
import { getPurchaseOrder } from '@/features/purchase-orders/api'
import { StatusBadge } from '@/features/purchase-orders/components/StatusBadge'
import type { PurchaseOrderStatus } from '@/features/purchase-orders/types'
import { Badge } from '@/shared/components/Badge'
import { Card, CardBody, CardHeader } from '@/shared/components/Card'
import { getProcess } from './api'
import { ActionPanel } from './components/ActionPanel'
import { AuditTimeline } from './components/AuditTimeline'
import { StepTimeline } from './components/StepTimeline'
import { userHasActed, userIsAssignee } from './types'

function formatCurrency(n: number | undefined): string {
  if (n === undefined) return '—'
  return n.toLocaleString(undefined, { style: 'currency', currency: 'USD' })
}

function formatDate(iso: string | null): string {
  if (iso === null) return '—'
  return new Date(iso).toLocaleString()
}

const processToneFor: Record<string, 'amber' | 'green' | 'red' | 'gray'> = {
  pending: 'amber',
  approved: 'green',
  rejected: 'red',
  cancelled: 'gray',
}

export function ProcessDetailPage() {
  const { id = '' } = useParams<{ id: string }>()
  const { user } = useAuth()

  const { data: process, isLoading, isError } = useQuery({
    queryKey: ['process', id],
    queryFn: () => getProcess(id),
    refetchOnWindowFocus: true,
  })

  const isPurchaseOrder = process?.subject_type === 'purchase_order'
  const subjectId = process?.subject_id

  const { data: po } = useQuery({
    queryKey: ['purchase-order', String(subjectId)],
    queryFn: () => getPurchaseOrder(subjectId as number),
    enabled: isPurchaseOrder && subjectId !== undefined,
  })

  if (isLoading) return <div className="p-8 text-sm text-gray-500">Loading…</div>
  if (isError || process === undefined) {
    return <div className="p-8 text-sm text-red-600">Couldn't load process.</div>
  }

  const currentStep = process.current_step_instance ?? null
  const showActions =
    user !== null &&
    process.status === 'pending' &&
    currentStep !== null &&
    currentStep.status === 'pending' &&
    userIsAssignee(currentStep, user.id) &&
    !userHasActed(currentStep, user.id)

  return (
    <div className="mx-auto max-w-5xl space-y-4 p-6">
      <div className="flex items-center justify-between">
        <Link to="/inbox" className="text-sm text-blue-600 hover:underline">
          ← Inbox
        </Link>
        {po !== undefined ? (
          <Link
            to={`/purchase-orders/${String(po.id)}`}
            className="text-sm text-blue-600 hover:underline"
          >
            Open purchase order →
          </Link>
        ) : null}
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-start justify-between gap-4">
            <div>
              <h1 className="text-lg font-semibold text-gray-900">
                {po?.title ?? `Process #${String(process.id)}`}
              </h1>
              <p className="mt-1 text-xs text-gray-500">
                Process #{process.id}
                {po !== undefined ? ` · PO #${String(po.id)}` : ''}
              </p>
            </div>
            <div className="flex items-center gap-2">
              <Badge tone={processToneFor[process.status] ?? 'gray'}>
                Process: {process.status}
              </Badge>
              {po !== undefined ? <StatusBadge status={po.status as PurchaseOrderStatus} /> : null}
            </div>
          </div>
        </CardHeader>
        {po !== undefined ? (
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
                <dt className="text-xs uppercase tracking-wide text-gray-500">Requester</dt>
                <dd className="text-gray-900">{po.requester?.name ?? '—'}</dd>
              </div>
              <div>
                <dt className="text-xs uppercase tracking-wide text-gray-500">Submitted</dt>
                <dd className="text-gray-900">{formatDate(po.submitted_at)}</dd>
              </div>
            </dl>
          </CardBody>
        ) : null}
      </Card>

      {showActions && currentStep !== null ? (
        <ActionPanel stepInstanceId={currentStep.id} processId={process.id} />
      ) : null}

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <h2 className="text-sm font-semibold text-gray-900">Steps</h2>
          </CardHeader>
          <CardBody>
            <StepTimeline
              steps={process.step_instances ?? []}
              currentStepInstanceId={process.current_step_instance_id}
            />
          </CardBody>
        </Card>

        <Card>
          <CardHeader>
            <h2 className="text-sm font-semibold text-gray-900">Audit log</h2>
          </CardHeader>
          <CardBody>
            <AuditTimeline entries={process.audit_log ?? []} />
          </CardBody>
        </Card>
      </div>
    </div>
  )
}
