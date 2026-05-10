import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
import { useAuth } from '@/features/auth/AuthProvider'
import { hasRole } from '@/features/auth/types'
import { Button } from '@/shared/components/Button'
import { cn } from '@/shared/utils/cn'

export function AppLayout() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  const handleSignOut = async () => {
    await logout()
    toast.message('Signed out')
    navigate('/login')
  }

  const navLinkClass = ({ isActive }: { isActive: boolean }): string =>
    cn(
      'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
      isActive ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100',
    )

  return (
    <div className="flex min-h-screen flex-col bg-gray-50">
      <nav className="border-b border-gray-200 bg-white">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-3">
          <div className="flex items-center gap-6">
            <Link to="/inbox" className="text-base font-semibold text-gray-900">
              Premind
            </Link>
            <div className="flex items-center gap-1">
              <NavLink to="/inbox" className={navLinkClass}>
                Inbox
              </NavLink>
              <NavLink to="/purchase-orders" className={navLinkClass}>
                Purchase Orders
              </NavLink>
              {hasRole(user, 'admin') ? (
                <NavLink to="/admin/workflows" className={navLinkClass}>
                  Workflows
                </NavLink>
              ) : null}
            </div>
          </div>
          <div className="flex items-center gap-3">
            {user !== null ? (
              <span className="text-sm text-gray-600">
                {user.name}{' '}
                <span className="text-xs text-gray-400">({user.roles.join(', ') || 'no role'})</span>
              </span>
            ) : null}
            <Button variant="ghost" size="sm" onClick={handleSignOut}>
              Sign out
            </Button>
          </div>
        </div>
      </nav>
      <main className="flex-1">
        <Outlet />
      </main>
    </div>
  )
}
