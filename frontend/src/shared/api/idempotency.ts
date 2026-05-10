import { useCallback, useState } from 'react'

export interface IdempotencyKey {
  key: string
  rotate: () => void
}

export function useIdempotencyKey(): IdempotencyKey {
  const [key, setKey] = useState(() => crypto.randomUUID())
  const rotate = useCallback(() => {
    setKey(crypto.randomUUID())
  }, [])
  return { key, rotate }
}
