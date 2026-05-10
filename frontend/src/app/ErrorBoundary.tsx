import { Component, type ErrorInfo, type ReactNode } from 'react'

interface Props {
  children: ReactNode
}

interface State {
  hasError: boolean
  error: Error | null
}

export class ErrorBoundary extends Component<Props, State> {
  state: State = { hasError: false, error: null }

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error }
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    if (import.meta.env.DEV) {
      console.error('Unhandled error:', error, info)
    }
  }

  handleReset = (): void => {
    this.setState({ hasError: false, error: null })
  }

  render(): ReactNode {
    if (this.state.hasError) {
      return (
        <div className="flex min-h-screen flex-col items-center justify-center gap-4 bg-gray-50 px-6 text-center">
          <h1 className="text-xl font-semibold text-gray-900">Something went wrong</h1>
          <p className="max-w-md text-sm text-gray-600">
            An unexpected error occurred. Try refreshing the page; if it keeps happening, sign out and back in.
          </p>
          <button
            type="button"
            onClick={() => window.location.assign('/')}
            className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
          >
            Go home
          </button>
          {import.meta.env.DEV && this.state.error !== null ? (
            <pre className="max-w-xl overflow-auto rounded-md bg-gray-900 p-3 text-left text-xs text-red-200">
              {this.state.error.message}
            </pre>
          ) : null}
        </div>
      )
    }
    return this.props.children
  }
}
