"use client";

import { PencilIcon, TrashIcon } from "@/components/icons/Icons";

function ActionButton({ title, icon, tone = "default", onClick, disabled }) {
  const toneClass =
    tone === "danger"
      ? "text-app-red hover:border-app-red/60 hover:bg-app-red/10"
      : "text-app-muted-light hover:border-app-yellow/60 hover:bg-app-yellow-soft hover:text-app-yellow";

  return (
    <button
      type="button"
      title={title}
      aria-label={title}
      disabled={disabled}
      onClick={(event) => {
        event.stopPropagation();
        onClick?.(event);
      }}
      className={`grid size-8 place-items-center rounded-lg border border-app-line bg-app-card-soft transition disabled:cursor-not-allowed disabled:opacity-50 ${toneClass}`}
    >
      {icon}
    </button>
  );
}

export default function RowActions({
  onEdit,
  onDelete,
  editTitle = "تعديل",
  deleteTitle = "حذف",
  disabled = false,
  className = "",
}) {
  return (
    <div className={`flex items-center justify-center gap-2 ${className}`}>
      {onEdit && (
        <ActionButton
          title={editTitle}
          onClick={onEdit}
          disabled={disabled}
          icon={<PencilIcon className="size-4" />}
        />
      )}
      {onDelete && (
        <ActionButton
          title={deleteTitle}
          tone="danger"
          onClick={onDelete}
          disabled={disabled}
          icon={<TrashIcon className="size-4" />}
        />
      )}
    </div>
  );
}
