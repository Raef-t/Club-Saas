import { GiftIcon } from "@/components/icons/Icons";

export default function EmptyState({ title = "لا توجد بيانات حالياً", description }) {
  return (
    <div className="card-shell flex min-h-60 flex-col items-center justify-center rounded-2xl px-6 py-12 text-center">
      <GiftIcon className="size-14 text-app-yellow" />
      <h3 className="mt-5 text-xl font-medium text-app-text">{title}</h3>
      {description && <p className="mt-2 max-w-md text-sm text-app-muted">{description}</p>}
    </div>
  );
}
