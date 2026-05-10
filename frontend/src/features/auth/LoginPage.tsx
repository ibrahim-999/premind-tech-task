import { zodResolver } from '@hookform/resolvers/zod'
import type { AxiosError } from 'axios'
import { ArrowRight, ShieldCheck, Workflow, Zap } from 'lucide-react'
import { useForm } from 'react-hook-form'
import { Navigate, useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
import { z } from 'zod'
import { Brand } from '@/shared/components/Brand'
import { Button } from '@/shared/components/Button'
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

const demoAccounts: Array<{ email: string; label: string; role: string }> = [
  { email: 'sara.manager@premind.local', label: 'Sara', role: 'manager' },
  { email: 'karim.finance@premind.local', label: 'Karim', role: 'finance head' },
  { email: 'chen.cfo@premind.local', label: 'Chen', role: 'CFO' },
  { email: 'ravi.cto@premind.local', label: 'Ravi', role: 'CTO' },
  { email: 'ali.dev@premind.local', label: 'Ali', role: 'requester' },
]

export function LoginPage() {
  const { login, user, isLoading } = useAuth()
  const navigate = useNavigate()

  const {
    register,
    handleSubmit,
    setError,
    setValue,
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

  const fillDemo = (email: string) => {
    setValue('email', email, { shouldValidate: true })
    setValue('password', 'secret', { shouldValidate: true })
  }

  return (
    <div className="grid min-h-screen grid-cols-1 lg:grid-cols-2">
      <aside className="relative hidden overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-brand-500 text-white lg:flex lg:flex-col lg:justify-between lg:px-12 lg:py-10">
        <div
          aria-hidden
          className="absolute inset-0 opacity-25"
          style={{
            backgroundImage:
              'radial-gradient(circle at 20% 20%, rgba(255,255,255,0.45) 0, transparent 35%), radial-gradient(circle at 80% 60%, rgba(255,255,255,0.30) 0, transparent 30%)',
          }}
        />
        <div className="relative flex items-center gap-2.5">
          <span className="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 text-xl font-bold text-white shadow-sm">
            P
          </span>
          <span className="text-xl font-semibold tracking-tight text-white">Premind</span>
        </div>
        <div className="relative space-y-8">
          <div>
            <h1 className="text-3xl font-semibold leading-tight text-white">
              Approvals that live in data,
              <br />
              not in if-statements.
            </h1>
            <p className="mt-3 max-w-md text-sm text-white/80">
              A configurable approval engine for purchase orders. Add steps,
              switch approvers, change thresholds — without shipping code.
            </p>
          </div>
          <ul className="space-y-3 text-sm">
            <li className="flex items-center gap-3">
              <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-white/15">
                <Workflow size={16} />
              </span>
              <span>Versioned workflows — in-flight processes never disrupted.</span>
            </li>
            <li className="flex items-center gap-3">
              <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-white/15">
                <Zap size={16} />
              </span>
              <span>Single, parallel-any, parallel-all, quorum — all four modes.</span>
            </li>
            <li className="flex items-center gap-3">
              <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-white/15">
                <ShieldCheck size={16} />
              </span>
              <span>Idempotent submits and approvals. Audit log on every event.</span>
            </li>
          </ul>
        </div>
        <p className="relative text-xs text-white/60">
          Demo build · password is <code className="rounded bg-white/15 px-1">secret</code> for everyone.
        </p>
      </aside>

      <main className="flex items-center justify-center px-4 py-10 sm:px-8">
        <div className="w-full max-w-md">
          <div className="mb-8 lg:hidden">
            <Brand size="md" />
          </div>
          <div>
            <h2 className="text-2xl font-semibold tracking-tight text-gray-900">
              Sign in to your account
            </h2>
            <p className="mt-1 text-sm text-gray-600">
              Use a seeded demo account to walk the scenarios.
            </p>
          </div>

          <form onSubmit={handleSubmit(onSubmit)} className="mt-8 space-y-4">
            <Field label="Email" htmlFor="email" error={errors.email?.message}>
              <Input
                id="email"
                type="email"
                autoComplete="email"
                placeholder="you@premind.local"
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
              {!isSubmitting ? <ArrowRight size={16} /> : null}
            </Button>
          </form>

          <div className="mt-8 border-t border-gray-200 pt-6">
            <p className="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500">
              Quick-fill a demo account
            </p>
            <div className="flex flex-wrap gap-2">
              {demoAccounts.map((acc) => (
                <button
                  key={acc.email}
                  type="button"
                  onClick={() => fillDemo(acc.email)}
                  className="group inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700"
                >
                  <span>{acc.label}</span>
                  <span className="text-gray-400 group-hover:text-brand-500">·</span>
                  <span className="text-gray-500 group-hover:text-brand-600">{acc.role}</span>
                </button>
              ))}
            </div>
          </div>
        </div>
      </main>
    </div>
  )
}
