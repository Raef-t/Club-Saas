import { PencilIcon, TrashIcon } from "@/components/icons/Icons";

/**
 * Renders consistent edit and delete actions for settings tables.
 */
export default function SettingsTableActions({ onEdit, onDelete }) {
  return (
    <div className="flex items-center justify-center gap-2">
      <button
        type="button"
        onClick={onEdit}
        className="grid size-8 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-muted-light transition hover:border-app-yellow/60 hover:text-app-yellow"
        title="تعديل"
        aria-label="تعديل"
      >
        <PencilIcon className="size-4" />
      </button>
      <button
        type="button"
        onClick={onDelete}
        className="grid size-8 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-muted-light transition hover:border-app-red/60 hover:text-app-red"
        title="حذف"
        aria-label="حذف"
      >
        <TrashIcon className="size-4" />
      </button>
    </div>
  );
}
