import { zodResolver } from '@hookform/resolvers/zod'
import type { AxiosError } from 'axios'
import { useForm } from 'react-hook-form'
import { Navigate, useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
import { z } from 'zod'
import { Button } from '@/shared/components/Button'
import { Card, CardBody, CardHeader } from '@/shared/components/Card'
import { Field, Input } from '@/shared/components/Input'
import { useAuth } from './AuthProvider'

const schema = z.object({
  email: z.string().email('Enter a valid email'),
  password: z.string().min(1, 'Password is required'),
})

type FormValues = z.infer<typeof schema>

interface ApiErrorBody {
  error?: string
  message?: string
}

export function LoginPage() {
  const { login, user, isLoading } = useAuth()
  const navigate = useNavigate()

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { email: '', password: '' },
  })

  if (isLoading) return null
  if (user !== null) return <Navigate to="/inbox" replace />

  const onSubmit = async (values: FormValues) => {
    try {
      await login(values.email, values.password)
      toast.success('Welcome back')
      navigate('/inbox')
    } catch (e) {
      const err = e as AxiosError<ApiErrorBody>
      const code = err.response?.data?.error
      if (code === 'invalid_credentials') {
        setError('password', { message: 'Email or password is incorrect' })
      } else if (code === 'account_inactive') {
        setError('email', { message: 'This account has been deactivated' })
      } else if (code === 'rate_limited') {
        toast.error('Too many login attempts. Try again in a minute.')
      } else if (code === 'validation_failed') {
        toast.error('Please check the form and try again.')
      } else {
        toast.error(err.response?.data?.message ?? 'Login failed')
      }
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
      <Card className="w-full max-w-md">
        <CardHeader>
          <h1 className="text-lg font-semibold">Sign in to Premind</h1>
          <p className="mt-1 text-sm text-gray-600">
            Approval engine for purchase orders.
          </p>
        </CardHeader>
        <CardBody>
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <Field label="Email" htmlFor="email" error={errors.email?.message}>
              <Input
                id="email"
                type="email"
                autoComplete="email"
                placeholder="ali.dev@premind.local"
                {...register('email')}
              />
            </Field>
            <Field label="Password" htmlFor="password" error={errors.password?.message}>
              <Input
                id="password"
                type="password"
                autoComplete="current-password"
                {...register('password')}
              />
            </Field>
            <Button type="submit" disabled={isSubmitting} className="w-full">
              {isSubmitting ? 'Signing in…' : 'Sign in'}
            </Button>
            <p className="pt-2 text-center text-xs text-gray-500">
              Demo password is <code className="rounded bg-gray-100 px-1 py-0.5">secret</code>.
              Try ali.dev@premind.local, sara.manager@premind.local, karim.finance@premind.local.
            </p>
          </form>
        </CardBody>
      </Card>
    </div>
  )
}
