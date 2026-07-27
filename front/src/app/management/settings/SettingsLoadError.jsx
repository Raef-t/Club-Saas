import Button from "@/components/ui/Button";

/**
 * Displays a recoverable settings query error with a retry action.
 */
export default function SettingsLoadError({ message, onRetry, isRetrying }) {
  if (!message) return null;

  return (
    <div
      className="flex flex-col gap-3 rounded-xl border border-app-red/30 bg-app-red/10 px-4 py-3 text-sm text-app-red sm:flex-row sm:items-center sm:justify-between"
      role="alert"
    >
      <p>{message}</p>
      <Button
        type="button"
        tone="outline"
        className="h-9 px-3 text-xs"
        loading={isRetrying}
        onClick={onRetry}
      >
        إعادة المحاولة
      </Button>
    </div>
  );
}
