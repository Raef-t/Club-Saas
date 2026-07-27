/**
 * QR symbol used by the scanner heading.
 */
export function QrCodeIcon({ className = "size-5" }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path
        d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Z"
        stroke="currentColor"
        strokeWidth="1.7"
      />
      <path d="M14 14h2.5v2.5H14V14Zm4 0h2v6h-6v-2h4v-4Z" fill="currentColor" />
    </svg>
  );
}

/**
 * Camera symbol shown before starting the QR scanner.
 */
export function CameraIcon({ className = "size-12" }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path
        d="M8.5 6 10 4h4l1.5 2H19a2 2 0 0 1 2 2v9.5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3.5Z"
        stroke="currentColor"
        strokeWidth="1.8"
        strokeLinejoin="round"
      />
      <circle cx="12" cy="12.7" r="3.4" stroke="currentColor" strokeWidth="1.8" />
    </svg>
  );
}

/**
 * Confirmation symbol used on a loaded member card.
 */
export function CheckCircleIcon({ className = "size-5" }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="1.7" />
      <path
        d="m8 12.2 2.5 2.5L16.5 9"
        stroke="currentColor"
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  );
}

/**
 * Stop symbol used by the temporary scanner action.
 */
export function StopIcon({ className = "size-4" }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <rect x="7" y="7" width="10" height="10" rx="2" fill="currentColor" />
    </svg>
  );
}
