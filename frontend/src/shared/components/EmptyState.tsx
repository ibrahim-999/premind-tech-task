import type { ReactNode } from 'react'
import { cn } from '@/shared/utils/cn'

interface Props {
  icon?: ReactNode
  title: string
  description?: string
  action?: ReactNode
  className?: string
}

export function EmptyState({ icon, title, description, action, className }: Props) {
  return (
    <div
      className={cn(
        'flex flex-col items-center justify-center px-6 py-16 text-center',
        className,
      )}
    >
      {icon !== undefined ? (
        <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
          {icon}
        </div>
      ) : null}
      <h3 className="text-base font-semibold text-gray-900">{title}</h3>
      {description !== undefined ? (
        <p className="mt-1 max-w-sm text-sm text-gray-500">{description}</p>
      ) : null}
      {action !== undefined ? <div className="mt-5">{action}</div> : null}
    </div>
  )
}
