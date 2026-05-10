import { Navigate, Route, Routes } from 'react-router-dom'

function PlaceholderPage({ title }: { title: string }) {
  return (
    <div className="mx-auto max-w-3xl p-8">
      <h1 className="text-2xl font-bold">{title}</h1>
      <p className="mt-2 text-gray-600">Coming soon.</p>
    </div>
  )
}

export function AppRoutes() {
  return (
    <Routes>
      <Route path="/" element={<Navigate to="/inbox" replace />} />
      <Route path="/login" element={<PlaceholderPage title="Login" />} />
      <Route path="/inbox" element={<PlaceholderPage title="Inbox" />} />
      <Route path="/purchase-orders" element={<PlaceholderPage title="Purchase Orders" />} />
      <Route path="/purchase-orders/new" element={<PlaceholderPage title="Create Purchase Order" />} />
      <Route path="/purchase-orders/:id" element={<PlaceholderPage title="Purchase Order Detail" />} />
      <Route path="/processes/:id" element={<PlaceholderPage title="Process Detail" />} />
      <Route path="*" element={<PlaceholderPage title="Not Found" />} />
    </Routes>
  )
}
