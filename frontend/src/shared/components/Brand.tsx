import { cn } from '@/shared/utils/cn'

interface BrandProps {
  size?: 'sm' | 'md' | 'lg'
  className?: string
  showWordmark?: boolean
}

const sizeClasses = {
  sm: { box: 'h-7 w-7', text: 'text-sm', radius: 'rounded-md' },
  md: { box: 'h-9 w-9', text: 'text-base', radius: 'rounded-lg' },
  lg: { box: 'h-12 w-12', text: 'text-xl', radius: 'rounded-xl' },
}

export function Brand({ size = 'md', className, showWordmark = true }: BrandProps) {
  const s = sizeClasses[size]
  return (
    <div className={cn('flex items-center gap-2.5', className)}>
      <span
        className={cn(
          'flex shrink-0 items-center justify-center bg-gradient-to-br from-brand-500 to-brand-700 text-white font-bold shadow-sm',
          s.box,
          s.radius,
        )}
        aria-hidden
      >
        P
      </span>
      {showWordmark ? (
        <span className={cn('font-semibold tracking-tight text-gray-900', s.text)}>
          Premind
        </span>
      ) : null}
    </div>
  )
}
