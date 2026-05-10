import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import type { AxiosError } from 'axios'
import { useForm } from 'react-hook-form'
import { Link, useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
import { z } from 'zod'
import { Button } from '@/shared/components/Button'
import { Card, CardBody, CardHeader } from '@/shared/components/Card'
import { Field, Input } from '@/shared/components/Input'
import { createWorkflow } from './api'

const schema = z.object({
  name: z.string().min(3, 'At least 3 characters').max(120),
  subject_type: z.string().min(1, 'Subject type is required').max(255),
})

type FormValues = z.infer<typeof schema>

interface ApiErrorBody {
  message?: string
}

export function NewWorkflowPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { name: '', subject_type: 'purchase_order' },
  })

  const mutation = useMutation({
    mutationFn: (values: FormValues) =>
      createWorkflow({ name: values.name, subject_type: values.subject_type }),
    onSuccess: async (workflow) => {
      await queryClient.invalidateQueries({ queryKey: ['workflows'] })
      toast.success('Workflow created — add the first version next')
      navigate(`/admin/workflows/${String(workflow.id)}`)
    },
    onError: (e) => {
      const err = e as AxiosError<ApiErrorBody>
      toast.error(err.response?.data?.message ?? 'Failed to create')
    },
  })

  return (
    <div className="mx-auto max-w-2xl p-6">
      <Link to="/admin/workflows" className="text-sm text-brand-600 hover:underline">
        ← All workflows
      </Link>

      <Card className="mt-4">
        <CardHeader>
          <h1 className="text-lg font-semibold text-gray-900">New workflow</h1>
          <p className="mt-1 text-sm text-gray-600">
            One workflow per subject type. Versions and steps come next.
          </p>
        </CardHeader>
        <CardBody>
          <form
            onSubmit={handleSubmit((values) => mutation.mutateAsync(values))}
            className="space-y-4"
          >
            <Field label="Name" htmlFor="name" error={errors.name?.message}>
              <Input
                id="name"
                placeholder="Standard Purchase Order Approval"
                {...register('name')}
              />
            </Field>
            <Field
              label="Subject type"
              htmlFor="subject_type"
              error={errors.subject_type?.message}
            >
              <Input
                id="subject_type"
                placeholder="purchase_order"
                {...register('subject_type')}
              />
            </Field>
            <p className="text-xs text-gray-500">
              The subject type binds this workflow to a specific Eloquent model (via morph map).
              Today only <code className="rounded bg-gray-100 px-1">purchase_order</code> is wired up.
            </p>
            <div className="flex justify-end">
              <Button type="submit" disabled={isSubmitting}>
                {isSubmitting ? 'Creating…' : 'Create workflow'}
              </Button>
            </div>
          </form>
        </CardBody>
      </Card>
    </div>
  )
}
