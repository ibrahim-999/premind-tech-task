import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import { Providers } from '@/app/Providers'
import { AppRoutes } from '@/app/routes'

const root = document.getElementById('root')
if (root === null) throw new Error('root element missing')

createRoot(root).render(
  <StrictMode>
    <Providers>
      <AppRoutes />
    </Providers>
  </StrictMode>,
)
