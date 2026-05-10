import { forwardRef, type InputHTMLAttributes, type TextareaHTMLAttributes } from 'react'
import { cn } from '@/shared/utils/cn'

const baseClasses =
  'block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-gray-400 transition-shadow focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 disabled:bg-gray-50 disabled:text-gray-500'

export const Input = forwardRef<HTMLInputElement, InputHTMLAttributes<HTMLInputElement>>(
  function Input({ className, ...props }, ref) {
    return <input ref={ref} className={cn(baseClasses, className)} {...props} />
  },
)

export const Textarea = forwardRef<
  HTMLTextAreaElement,
  TextareaHTMLAttributes<HTMLTextAreaElement>
>(function Textarea({ className, ...props }, ref) {
  return <textarea ref={ref} className={cn(baseClasses, 'min-h-[80px]', className)} {...props} />
})

interface FieldProps {
  label: string
  error?: string
  htmlFor?: string
  className?: string
  children: React.ReactNode
}

export function Field({ label, error, htmlFor, className, children }: FieldProps) {
  return (
    <div className={cn('space-y-1', className)}>
      <label htmlFor={htmlFor} className="block text-sm font-medium text-gray-700">
        {label}
      </label>
      {children}
      {error !== undefined && error !== '' ? (
        <p className="text-xs text-red-600">{error}</p>
      ) : null}
    </div>
  )
}
