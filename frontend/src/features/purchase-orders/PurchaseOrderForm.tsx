import { zodResolver } from '@hookform/resolvers/zod'
import { Trash2 } from 'lucide-react'
import { useForm, useFieldArray, type SubmitHandler } from 'react-hook-form'
import { Button } from '@/shared/components/Button'
import { Field, Input, Textarea } from '@/shared/components/Input'
import { purchaseOrderSchema, type PurchaseOrderFormValues } from './schemas'

interface Props {
  initial?: PurchaseOrderFormValues
  onSubmit: SubmitHandler<PurchaseOrderFormValues>
  submitLabel?: string
  isSubmitting?: boolean
}

const emptyDefaults: PurchaseOrderFormValues = {
  title: '',
  description: '',
  category: '',
  department_id: null,
  items: [{ name: '', quantity: 1, unit_price: 0 }],
}

function formatCurrency(n: number): string {
  return n.toLocaleString(undefined, { style: 'currency', currency: 'USD' })
}

export function PurchaseOrderForm({ initial, onSubmit, submitLabel = 'Create', isSubmitting }: Props) {
  const {
    register,
    control,
    handleSubmit,
    watch,
    formState: { errors },
  } = useForm<PurchaseOrderFormValues>({
    resolver: zodResolver(purchaseOrderSchema),
    defaultValues: initial ?? emptyDefaults,
  })

  const { fields, append, remove } = useFieldArray({ control, name: 'items' })
  const items = watch('items')
  const total = items.reduce((sum, item) => {
    const q = Number(item.quantity) || 0
    const p = Number(item.unit_price) || 0
    return sum + q * p
  }, 0)

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Field label="Title" htmlFor="title" error={errors.title?.message}>
          <Input id="title" placeholder="2x MacBook Pro" {...register('title')} />
        </Field>
        <Field label="Category" htmlFor="category" error={errors.category?.message}>
          <Input id="category" placeholder="IT" {...register('category')} />
        </Field>
      </div>

      <Field label="Description (optional)" htmlFor="description" error={errors.description?.message}>
        <Textarea id="description" rows={2} {...register('description')} />
      </Field>

      <Field
        label="Department ID (optional)"
        htmlFor="department_id"
        error={errors.department_id?.message}
      >
        <Input
          id="department_id"
          type="number"
          min={1}
          {...register('department_id', {
            setValueAs: (v) => (v === '' || v === null ? null : Number(v)),
          })}
        />
      </Field>

      <div>
        <div className="mb-2 flex items-center justify-between">
          <h2 className="text-sm font-semibold text-gray-900">Line items</h2>
          <Button
            type="button"
            variant="secondary"
            size="sm"
            onClick={() => {
              append({ name: '', quantity: 1, unit_price: 0 })
            }}
          >
            Add item
          </Button>
        </div>
        <div className="overflow-hidden rounded-md border border-gray-200">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500">
              <tr>
                <th className="px-3 py-2 text-left">Name</th>
                <th className="px-3 py-2 text-right">Qty</th>
                <th className="px-3 py-2 text-right">Unit price</th>
                <th className="px-3 py-2 text-right">Line total</th>
                <th className="w-10 px-3 py-2"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {fields.map((field, index) => {
                const itemErrors = errors.items?.[index]
                const q = Number(items[index]?.quantity) || 0
                const p = Number(items[index]?.unit_price) || 0
                return (
                  <tr key={field.id} className="align-top">
                    <td className="px-3 py-2">
                      <Input placeholder="Item name" {...register(`items.${index}.name`)} />
                      {itemErrors?.name?.message !== undefined ? (
                        <p className="mt-1 text-xs text-red-600">{itemErrors.name.message}</p>
                      ) : null}
                    </td>
                    <td className="px-3 py-2">
                      <Input
                        type="number"
                        min={1}
                        className="text-right"
                        {...register(`items.${index}.quantity`, { valueAsNumber: true })}
                      />
                      {itemErrors?.quantity?.message !== undefined ? (
                        <p className="mt-1 text-xs text-red-600">{itemErrors.quantity.message}</p>
                      ) : null}
                    </td>
                    <td className="px-3 py-2">
                      <Input
                        type="number"
                        min={0}
                        step="0.01"
                        className="text-right"
                        {...register(`items.${index}.unit_price`, { valueAsNumber: true })}
                      />
                      {itemErrors?.unit_price?.message !== undefined ? (
                        <p className="mt-1 text-xs text-red-600">{itemErrors.unit_price.message}</p>
                      ) : null}
                    </td>
                    <td className="px-3 py-2 text-right text-gray-700">
                      {formatCurrency(q * p)}
                    </td>
                    <td className="px-3 py-2">
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        disabled={fields.length <= 1}
                        onClick={() => {
                          remove(index)
                        }}
                        aria-label="Remove item"
                      >
                        <Trash2 size={16} />
                      </Button>
                    </td>
                  </tr>
                )
              })}
            </tbody>
            <tfoot>
              <tr className="bg-gray-50">
                <td className="px-3 py-2 text-right font-medium" colSpan={3}>
                  Total
                </td>
                <td className="px-3 py-2 text-right font-semibold text-gray-900">
                  {formatCurrency(total)}
                </td>
                <td />
              </tr>
            </tfoot>
          </table>
        </div>
        {errors.items?.message !== undefined ? (
          <p className="mt-1 text-xs text-red-600">{errors.items.message}</p>
        ) : null}
      </div>

      <div className="flex justify-end gap-3">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting === true ? 'Saving…' : submitLabel}
        </Button>
      </div>
    </form>
  )
}
