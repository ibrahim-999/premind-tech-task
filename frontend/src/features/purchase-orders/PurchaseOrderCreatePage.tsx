import { useMutation, useQueryClient } from '@tanstack/react-query'
import type { AxiosError } from 'axios'
import { Link, useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
import { Button } from '@/shared/components/Button'
import { Card, CardBody, CardHeader } from '@/shared/components/Card'
import { createPurchaseOrder } from './api'
import { PurchaseOrderForm } from './PurchaseOrderForm'
import type { PurchaseOrderFormValues } from './schemas'

export function PurchaseOrderCreatePage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const mutation = useMutation({
    mutationFn: createPurchaseOrder,
    onSuccess: async (po) => {
      await queryClient.invalidateQueries({ queryKey: ['purchase-orders'] })
      toast.success('Draft saved')
      navigate(`/purchase-orders/${String(po.id)}`)
    },
    onError: (e) => {
      const err = e as AxiosError<{ message?: string }>
      toast.error(err.response?.data?.message ?? 'Failed to save')
    },
  })

  const onSubmit = async (values: PurchaseOrderFormValues) => {
    await mutation.mutateAsync({
      title: values.title,
      description: values.description ?? null,
      category: values.category,
      department_id: values.department_id ?? null,
      items: values.items,
    })
  }

  return (
    <div className="mx-auto max-w-4xl p-6">
      <div className="mb-4 flex items-center justify-between">
        <h1 className="text-xl font-semibold text-gray-900">New Purchase Order</h1>
        <Link to="/purchase-orders">
          <Button variant="ghost" size="sm">Cancel</Button>
        </Link>
      </div>
      <Card>
        <CardHeader>
          <p className="text-sm text-gray-600">
            Fill in the items and save as a draft. You can edit until you submit.
          </p>
        </CardHeader>
        <CardBody>
          <PurchaseOrderForm
            onSubmit={onSubmit}
            submitLabel="Save draft"
            isSubmitting={mutation.isPending}
          />
        </CardBody>
      </Card>
    </div>
  )
}
