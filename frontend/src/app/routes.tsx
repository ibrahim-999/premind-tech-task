import { Navigate, Route, Routes } from 'react-router-dom'
import { LoginPage } from '@/features/auth/LoginPage'
import { AppLayout } from './AppLayout'
import { RequireAdmin, RequireAuth } from './guards'

function PlaceholderPage({ title }: { title: string }) {
  return (
    <div className="mx-auto max-w-3xl p-8">
      <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
      <p className="mt-2 text-gray-600">Coming soon.</p>
    </div>
  )
}

export function AppRoutes() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />

      <Route
        element={
          <RequireAuth>
            <AppLayout />
          </RequireAuth>
        }
      >
        <Route path="/" element={<Navigate to="/inbox" replace />} />
        <Route path="/inbox" element={<PlaceholderPage title="Inbox" />} />
        <Route path="/purchase-orders" element={<PlaceholderPage title="Purchase Orders" />} />
        <Route path="/purchase-orders/new" element={<PlaceholderPage title="Create Purchase Order" />} />
        <Route path="/purchase-orders/:id" element={<PlaceholderPage title="Purchase Order Detail" />} />
        <Route path="/processes/:id" element={<PlaceholderPage title="Process Detail" />} />
        <Route
          path="/admin/workflows"
          element={
            <RequireAdmin>
              <PlaceholderPage title="Workflows (admin)" />
            </RequireAdmin>
          }
        />
        <Route path="*" element={<PlaceholderPage title="Not Found" />} />
      </Route>
    </Routes>
  )
}
