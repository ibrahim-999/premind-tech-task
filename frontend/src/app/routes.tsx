import { Navigate, Route, Routes } from 'react-router-dom'
import { LoginPage } from '@/features/auth/LoginPage'
import { PurchaseOrderCreatePage } from '@/features/purchase-orders/PurchaseOrderCreatePage'
import { PurchaseOrderDetailPage } from '@/features/purchase-orders/PurchaseOrderDetailPage'
import { PurchaseOrderEditPage } from '@/features/purchase-orders/PurchaseOrderEditPage'
import { PurchaseOrderListPage } from '@/features/purchase-orders/PurchaseOrderListPage'
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
        <Route path="/purchase-orders" element={<PurchaseOrderListPage />} />
        <Route path="/purchase-orders/new" element={<PurchaseOrderCreatePage />} />
        <Route path="/purchase-orders/:id" element={<PurchaseOrderDetailPage />} />
        <Route path="/purchase-orders/:id/edit" element={<PurchaseOrderEditPage />} />
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
