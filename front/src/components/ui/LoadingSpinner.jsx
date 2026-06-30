export default function LoadingSpinner({ className = "size-5", label = "جاري التحميل" }) {
  return (
    <span
      className={`inline-block animate-spin rounded-full border-2 border-current border-t-transparent ${className}`}
      role="status"
      aria-label={label}
    />
  );
}
