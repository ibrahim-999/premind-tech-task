import axios, {
  type AxiosError,
  type AxiosRequestConfig,
  type InternalAxiosRequestConfig,
} from 'axios'
import { getAccessToken, setAccessToken } from './storage'

const baseURL = import.meta.env.VITE_API_URL ?? '/api/v1'

export const apiClient = axios.create({
  baseURL,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

apiClient.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = getAccessToken()
  if (token !== null) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

let refreshInflight: Promise<string | null> | null = null

async function refreshAccessToken(): Promise<string | null> {
  if (refreshInflight !== null) {
    return refreshInflight
  }

  refreshInflight = axios
    .post<{ access_token: string }>(
      `${baseURL}/auth/refresh`,
      {},
      { headers: { Authorization: `Bearer ${getAccessToken() ?? ''}` } },
    )
    .then((res) => {
      setAccessToken(res.data.access_token)
      return res.data.access_token
    })
    .catch(() => {
      setAccessToken(null)
      return null
    })
    .finally(() => {
      refreshInflight = null
    })

  return refreshInflight
}

interface ApiError {
  error?: string
  message?: string
}

apiClient.interceptors.response.use(
  (response) => response,
  async (error: AxiosError<ApiError>) => {
    const original = error.config as
      | (AxiosRequestConfig & { _retried?: boolean })
      | undefined
    const status = error.response?.status
    const code = error.response?.data?.error

    if (
      status === 401 &&
      code === 'token_expired' &&
      original !== undefined &&
      original._retried !== true
    ) {
      original._retried = true
      const token = await refreshAccessToken()
      if (token !== null) {
        original.headers = {
          ...(original.headers ?? {}),
          Authorization: `Bearer ${token}`,
        }
        return apiClient(original)
      }
    }

    if (
      status === 401 &&
      (code === 'token_invalid' ||
        code === 'token_blacklisted' ||
        code === 'token_missing')
    ) {
      setAccessToken(null)
      if (window.location.pathname !== '/login') {
        window.location.assign('/login')
      }
    }

    return Promise.reject(error)
  },
)
