import { Navigate, useLocation, type Location } from 'react-router-dom'
import { useAuth } from '@/features/auth/AuthProvider'
import { hasRole } from '@/features/auth/types'

interface LocationState {
  from?: Location
}

export function RequireAuth({ children }: { children: React.ReactNode }) {
  const { user, isLoading } = useAuth()
  const location = useLocation()

  if (isLoading) {
    return <FullPageSpinner />
  }
  if (user === null) {
    const state: LocationState = { from: location }
    return <Navigate to="/login" replace state={state} />
  }
  return <>{children}</>
}

export function RequireAdmin({ children }: { children: React.ReactNode }) {
  const { user, isLoading } = useAuth()

  if (isLoading) {
    return <FullPageSpinner />
  }
  if (user === null) {
    return <Navigate to="/login" replace />
  }
  if (!hasRole(user, 'admin')) {
    return <Navigate to="/inbox" replace />
  }
  return <>{children}</>
}

function FullPageSpinner() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50">
      <div className="h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-blue-600" />
    </div>
  )
}
