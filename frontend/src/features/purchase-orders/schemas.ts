import { z } from 'zod'

export const purchaseOrderItemSchema = z.object({
  name: z.string().min(1, 'Name is required').max(200),
  quantity: z
    .number({ invalid_type_error: 'Required' })
    .int('Whole numbers only')
    .min(1, 'At least 1'),
  unit_price: z
    .number({ invalid_type_error: 'Required' })
    .min(0, 'Cannot be negative'),
})

export const purchaseOrderSchema = z.object({
  title: z
    .string()
    .min(3, 'At least 3 characters')
    .max(200, 'At most 200 characters'),
  description: z.string().optional(),
  category: z
    .string()
    .min(1, 'Category is required')
    .max(64, 'At most 64 characters'),
  department_id: z
    .number({ invalid_type_error: 'Must be a number' })
    .int()
    .positive()
    .optional()
    .nullable(),
  items: z.array(purchaseOrderItemSchema).min(1, 'Add at least one line item'),
})

export type PurchaseOrderFormValues = z.infer<typeof purchaseOrderSchema>
