import { apiClient } from '@/shared/api/client'
import type { LoginResponse, User } from './types'

export async function loginRequest(email: string, password: string): Promise<LoginResponse> {
  const res = await apiClient.post<LoginResponse>('/auth/login', { email, password })
  return res.data
}

export async function logoutRequest(): Promise<void> {
  await apiClient.post('/auth/logout', {})
}

export async function meRequest(): Promise<User> {
  const res = await apiClient.get<{ data: User }>('/auth/me')
  return res.data.data
}
