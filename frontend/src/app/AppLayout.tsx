import { LogOut } from 'lucide-react'
import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
import { useAuth } from '@/features/auth/AuthProvider'
import { hasRole } from '@/features/auth/types'
import { Avatar } from '@/shared/components/Avatar'
import { Brand } from '@/shared/components/Brand'
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
      'relative rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
      isActive
        ? 'text-brand-700 bg-brand-50'
        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
    )

  return (
    <div className="flex min-h-screen flex-col bg-gray-50">
      <header className="sticky top-0 z-20 border-b border-gray-200 bg-white/90 backdrop-blur supports-[backdrop-filter]:bg-white/75">
        <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-3">
          <div className="flex items-center gap-6">
            <Link to="/inbox" className="shrink-0">
              <Brand size="sm" />
            </Link>
            <nav className="flex items-center gap-1">
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
            </nav>
          </div>
          <div className="flex items-center gap-3">
            {user !== null ? (
              <div className="flex items-center gap-2.5">
                <Avatar name={user.name} size="sm" />
                <div className="hidden text-right sm:block">
                  <div className="text-sm font-medium leading-tight text-gray-900">
                    {user.name}
                  </div>
                  <div className="text-xs leading-tight text-gray-500">
                    {user.roles.length > 0 ? user.roles.join(', ') : 'no role'}
                  </div>
                </div>
              </div>
            ) : null}
            <Button variant="ghost" size="sm" onClick={handleSignOut} aria-label="Sign out">
              <LogOut size={16} />
              <span className="hidden sm:inline">Sign out</span>
            </Button>
          </div>
        </div>
      </header>
      <main className="flex-1">
        <Outlet />
      </main>
    </div>
  )
}
