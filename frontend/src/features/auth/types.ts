export interface User {
  id: number
  name: string
  email: string
  is_active: boolean
  department_id: number | null
  is_department_head: boolean
  manager_id: number | null
  roles: string[]
  created_at: string | null
}

export interface LoginResponse {
  access_token: string
  token_type: string
  expires_in: number
  user: User
}

export function hasRole(user: User | null, role: string): boolean {
  return user !== null && user.roles.includes(role)
}
