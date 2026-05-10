import { useMutation, useQueryClient } from '@tanstack/react-query'
import type { AxiosError } from 'axios'
import { useState } from 'react'
import { toast } from 'sonner'
import { useIdempotencyKey } from '@/shared/api/idempotency'
import { Button } from '@/shared/components/Button'
import { Card, CardBody, CardHeader } from '@/shared/components/Card'
import { Field, Textarea } from '@/shared/components/Input'
import { approveStep, rejectStep } from '../api'
import type { ApprovalProcess } from '../types'

interface ApiErrorBody {
  error?: string
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

export function ActionPanel({
  stepInstanceId,
  processId,
}: {
  stepInstanceId: number
  processId: number
}) {
  const queryClient = useQueryClient()
  const approveKey = useIdempotencyKey()
  const rejectKey = useIdempotencyKey()
  const [mode, setMode] = useState<'idle' | 'approve' | 'reject'>('idle')
  const [comment, setComment] = useState('')
  const [reason, setReason] = useState('')
  const [reasonError, setReasonError] = useState<string | undefined>(undefined)

  const invalidate = async (next: ApprovalProcess) => {
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: ['process', String(processId)] }),
      queryClient.invalidateQueries({ queryKey: ['inbox'] }),
      queryClient.invalidateQueries({
        queryKey: ['purchase-order', String(next.subject_id)],
      }),
      queryClient.invalidateQueries({ queryKey: ['purchase-orders'] }),
    ])
  }

  const approveMutation = useMutation({
    mutationFn: () => approveStep(stepInstanceId, comment.trim() || null, approveKey.key),
    onSuccess: async (next) => {
      approveKey.rotate()
      setMode('idle')
      setComment('')
      await invalidate(next)
      toast.success('Approved')
    },
    onError: (e) => {
      showApiError(e, 'Approve failed')
    },
  })

  const rejectMutation = useMutation({
    mutationFn: () => rejectStep(stepInstanceId, reason.trim(), rejectKey.key),
    onSuccess: async (next) => {
      rejectKey.rotate()
      setMode('idle')
      setReason('')
      await invalidate(next)
      toast.success('Rejected')
    },
    onError: (e) => {
      showApiError(e, 'Reject failed')
    },
  })

  if (mode === 'idle') {
    return (
      <Card>
        <CardHeader>
          <h2 className="text-sm font-semibold text-gray-900">Your action</h2>
          <p className="mt-1 text-xs text-gray-600">
            You're an assignee on the current step.
          </p>
        </CardHeader>
        <CardBody>
          <div className="flex gap-2">
            <Button onClick={() => setMode('approve')}>Approve</Button>
            <Button variant="danger" onClick={() => setMode('reject')}>Reject</Button>
          </div>
        </CardBody>
      </Card>
    )
  }

  if (mode === 'approve') {
    return (
      <Card>
        <CardHeader>
          <h2 className="text-sm font-semibold text-gray-900">Approve step</h2>
        </CardHeader>
        <CardBody>
          <Field label="Comment (optional)" htmlFor="approve-comment">
            <Textarea
              id="approve-comment"
              rows={3}
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              placeholder="Looks good"
            />
          </Field>
          <div className="mt-3 flex justify-end gap-2">
            <Button variant="ghost" size="sm" onClick={() => setMode('idle')}>
              Cancel
            </Button>
            <Button
              size="sm"
              disabled={approveMutation.isPending}
              onClick={() => approveMutation.mutate()}
            >
              {approveMutation.isPending ? 'Approving…' : 'Confirm approval'}
            </Button>
          </div>
        </CardBody>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader>
        <h2 className="text-sm font-semibold text-gray-900">Reject step</h2>
      </CardHeader>
      <CardBody>
        <Field label="Reason (required)" htmlFor="reject-reason" error={reasonError}>
          <Textarea
            id="reject-reason"
            rows={3}
            value={reason}
            onChange={(e) => {
              setReason(e.target.value)
              setReasonError(undefined)
            }}
            placeholder="Quote missing breakdown"
          />
        </Field>
        <div className="mt-3 flex justify-end gap-2">
          <Button variant="ghost" size="sm" onClick={() => setMode('idle')}>
            Cancel
          </Button>
          <Button
            variant="danger"
            size="sm"
            disabled={rejectMutation.isPending}
            onClick={() => {
              if (reason.trim().length < 3) {
                setReasonError('Reason must be at least 3 characters')
                return
              }
              rejectMutation.mutate()
            }}
          >
            {rejectMutation.isPending ? 'Rejecting…' : 'Confirm rejection'}
          </Button>
        </div>
      </CardBody>
    </Card>
  )
}
