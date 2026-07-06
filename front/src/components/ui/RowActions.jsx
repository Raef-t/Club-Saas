"use client";

import Link from "next/link";
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

function ActionLink({ title, icon, href, disabled }) {
  return (
    <Link
      href={disabled ? "#" : href}
      title={title}
      aria-label={title}
      onClick={(event) => {
        event.stopPropagation();
        if (disabled) event.preventDefault();
      }}
      className="grid size-8 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-muted-light transition hover:border-app-yellow/60 hover:bg-app-yellow-soft hover:text-app-yellow aria-disabled:cursor-not-allowed aria-disabled:opacity-50"
      aria-disabled={disabled}
    >
      {icon}
    </Link>
  );
}

export default function RowActions({
  onEdit,
  editHref,
  onDelete,
  editTitle = "تعديل",
  deleteTitle = "حذف",
  disabled = false,
  className = "",
}) {
  return (
    <div className={`flex items-center justify-center gap-2 ${className}`}>
      {editHref ? (
        <ActionLink
          title={editTitle}
          href={editHref}
          disabled={disabled}
          icon={<PencilIcon className="size-4" />}
        />
      ) : onEdit ? (
        <ActionButton
          title={editTitle}
          onClick={onEdit}
          disabled={disabled}
          icon={<PencilIcon className="size-4" />}
        />
      ) : null}
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
