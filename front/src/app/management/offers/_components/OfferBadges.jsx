import { getOfferStatus } from "../_lib/offerPresentation";

const statusToneClasses = {
  success: "border-emerald-500/30 bg-emerald-500/15 text-emerald-400",
  warning: "border-amber-500/30 bg-amber-500/15 text-amber-400",
  danger: "border-rose-500/30 bg-rose-500/15 text-rose-400",
};

export function OfferStatusBadge({ offer, className = "" }) {
  const status = getOfferStatus(offer);

  return (
    <span
      className={`inline-flex min-w-20 items-center justify-center rounded-md border px-2.5 py-1 text-xs font-medium ${statusToneClasses[status.tone]} ${className}`}
    >
      {status.label}
    </span>
  );
}

export function OfferTypeBadge({ type, compact = false }) {
  const isSingleChoice = type === "single_choice";

  return (
    <span
      className={`inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 font-medium ${
        compact ? "text-[10px]" : "text-xs"
      } ${
        isSingleChoice
          ? "border-purple-500/30 bg-purple-500/15 text-purple-300"
          : "border-blue-500/30 bg-blue-500/15 text-blue-300"
      }`}
    >
      {isSingleChoice ? "يختار المشترك فعالية واحدة" : "باقة مجمعة"}
    </span>
  );
}
