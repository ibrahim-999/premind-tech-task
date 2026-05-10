import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { AxiosError } from 'axios'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { toast } from 'sonner'
import { Button } from '@/shared/components/Button'
import { Card, CardBody, CardHeader } from '@/shared/components/Card'
import { getPurchaseOrder, updatePurchaseOrder } from './api'
import { PurchaseOrderForm } from './PurchaseOrderForm'
import type { PurchaseOrderFormValues } from './schemas'
import { isEditable } from './types'

export function PurchaseOrderEditPage() {
  const { id = '' } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const { data: po, isLoading, isError } = useQuery({
    queryKey: ['purchase-order', id],
    queryFn: () => getPurchaseOrder(id),
  })

  const mutation = useMutation({
    mutationFn: (values: PurchaseOrderFormValues) =>
      updatePurchaseOrder(id, {
        title: values.title,
        description: values.description ?? null,
        category: values.category,
        department_id: values.department_id ?? null,
        items: values.items,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['purchase-order', id] })
      await queryClient.invalidateQueries({ queryKey: ['purchase-orders'] })
      toast.success('Saved')
      navigate(`/purchase-orders/${id}`)
    },
    onError: (e) => {
      const err = e as AxiosError<{ message?: string }>
      toast.error(err.response?.data?.message ?? 'Save failed')
    },
  })

  if (isLoading) {
    return <div className="p-8 text-sm text-gray-500">Loading…</div>
  }
  if (isError || po === undefined) {
    return <div className="p-8 text-sm text-red-600">Couldn't load purchase order.</div>
  }
  if (!isEditable(po.status)) {
    return (
      <div className="mx-auto max-w-3xl p-6">
        <Card>
          <CardBody>
            <p className="text-sm text-gray-700">
              This purchase order can no longer be edited (status: {po.status}).
            </p>
            <Link to={`/purchase-orders/${id}`} className="mt-3 inline-block text-sm text-blue-600 hover:underline">
              Back to detail
            </Link>
          </CardBody>
        </Card>
      </div>
    )
  }

  const initial: PurchaseOrderFormValues = {
    title: po.title,
    description: po.description ?? '',
    category: po.category,
    department_id: po.department_id,
    items: (po.items ?? []).map((item) => ({
      name: item.name,
      quantity: item.quantity,
      unit_price: item.unit_price,
    })),
  }

  return (
    <div className="mx-auto max-w-4xl p-6">
      <div className="mb-4 flex items-center justify-between">
        <h1 className="text-xl font-semibold text-gray-900">Edit Purchase Order</h1>
        <Link to={`/purchase-orders/${id}`}>
          <Button variant="ghost" size="sm">Cancel</Button>
        </Link>
      </div>
      <Card>
        <CardHeader>
          <p className="text-sm text-gray-600">
            Editing PO #{id}. Status: {po.status}.
          </p>
        </CardHeader>
        <CardBody>
          <PurchaseOrderForm
            initial={initial}
            onSubmit={(values) => mutation.mutateAsync(values)}
            submitLabel="Save changes"
            isSubmitting={mutation.isPending}
          />
        </CardBody>
      </Card>
    </div>
  )
}
