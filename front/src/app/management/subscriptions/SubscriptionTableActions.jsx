"use client";

import Link from "next/link";
import { useCallback, useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { PencilIcon, RefreshIcon, TrashIcon } from "@/components/icons/Icons";

const MENU_WIDTH = 192;
const MENU_EDGE_GAP = 8;

function MoreVerticalIcon({ className = "size-4" }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <circle cx="12" cy="5" r="1.5" />
      <circle cx="12" cy="12" r="1.5" />
      <circle cx="12" cy="19" r="1.5" />
    </svg>
  );
}

function ViewIcon({ className = "size-4" }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path
        d="M2.75 12s3.25-5.25 9.25-5.25S21.25 12 21.25 12 18 17.25 12 17.25 2.75 12 2.75 12Z"
        stroke="currentColor"
        strokeWidth="1.8"
        strokeLinejoin="round"
      />
      <circle cx="12" cy="12" r="2.25" stroke="currentColor" strokeWidth="1.8" />
    </svg>
  );
}

const MENU_ITEM_CLASS =
  "flex h-10 w-full items-center gap-2.5 rounded-lg px-3 text-right text-sm transition disabled:cursor-not-allowed disabled:opacity-50";

/** Keeps every row action behind one compact three-dot menu. */
export default function SubscriptionTableActions({
  subscription,
  canView,
  canUpdate,
  canDelete,
  canRenew,
  isBusy,
  onView,
  onDelete,
  onRenew,
}) {
  const [open, setOpen] = useState(false);
  const [menuPosition, setMenuPosition] = useState(null);
  const triggerRef = useRef(null);
  const menuRef = useRef(null);
  const canRenewSubscription = canRenew && subscription.status === "finished";
  const hasActions = canView || canUpdate || canDelete || canRenewSubscription;

  const updateMenuPosition = useCallback(() => {
    const trigger = triggerRef.current;
    if (!trigger) return;

    const rect = trigger.getBoundingClientRect();
    const visibleActionCount = [canView, canUpdate, canRenewSubscription, canDelete].filter(
      Boolean,
    ).length;
    const menuHeight = visibleActionCount * 40 + 8;
    const viewportWidth = document.documentElement.clientWidth || window.innerWidth;
    const viewportHeight = document.documentElement.clientHeight || window.innerHeight;
    const left = Math.min(
      Math.max(MENU_EDGE_GAP, rect.right - MENU_WIDTH),
      viewportWidth - MENU_WIDTH - MENU_EDGE_GAP,
    );
    const top =
      rect.bottom + 6 + menuHeight <= viewportHeight
        ? rect.bottom + 6
        : Math.max(MENU_EDGE_GAP, rect.top - menuHeight - 6);

    setMenuPosition({ left, top });
  }, [canDelete, canRenewSubscription, canUpdate, canView]);

  useEffect(() => {
    if (!open) return;

    updateMenuPosition();

    function handlePointerDown(event) {
      const clickedTrigger = triggerRef.current?.contains(event.target);
      const clickedMenu = menuRef.current?.contains(event.target);
      if (!clickedTrigger && !clickedMenu) setOpen(false);
    }

    function handleKeyDown(event) {
      if (event.key === "Escape") {
        setOpen(false);
        triggerRef.current?.focus();
      }
    }

    document.addEventListener("pointerdown", handlePointerDown);
    document.addEventListener("keydown", handleKeyDown);
    window.addEventListener("resize", updateMenuPosition);
    window.addEventListener("scroll", updateMenuPosition, true);

    return () => {
      document.removeEventListener("pointerdown", handlePointerDown);
      document.removeEventListener("keydown", handleKeyDown);
      window.removeEventListener("resize", updateMenuPosition);
      window.removeEventListener("scroll", updateMenuPosition, true);
    };
  }, [open, updateMenuPosition]);

  if (!hasActions) return <span className="text-app-muted-light">-</span>;

  function runAction(callback) {
    setOpen(false);
    callback?.(subscription);
  }

  return (
    <div className="flex w-full items-center justify-center" dir="rtl">
      <button
        ref={triggerRef}
        type="button"
        title="إجراءات الاشتراك"
        aria-label="إجراءات الاشتراك"
        aria-haspopup="menu"
        aria-expanded={open}
        className="grid size-9 shrink-0 place-items-center rounded-lg border border-app-line bg-slate-500/15 text-app-muted-light transition hover:border-app-yellow/50 hover:bg-app-yellow/10 hover:text-app-yellow focus:outline-none focus:ring-1 focus:ring-app-yellow/70"
        onClick={(event) => {
          event.stopPropagation();
          if (!open) updateMenuPosition();
          setOpen((current) => !current);
        }}
      >
        <MoreVerticalIcon />
      </button>

      {open &&
        menuPosition &&
        createPortal(
          <div
            ref={menuRef}
            role="menu"
            aria-label="إجراءات الاشتراك"
            dir="rtl"
            className="fixed z-[110] rounded-xl border border-app-line bg-app-card p-1 shadow-[var(--app-elevated-shadow)]"
            style={{ left: menuPosition.left, top: menuPosition.top, width: MENU_WIDTH }}
            onClick={(event) => event.stopPropagation()}
          >
            {canView && (
              <button
                type="button"
                role="menuitem"
                className={`${MENU_ITEM_CLASS} text-app-text hover:bg-app-card-hover`}
                onClick={() => runAction(onView)}
              >
                <ViewIcon />
                عرض التفاصيل
              </button>
            )}

            {canUpdate && (
              <Link
                href={`/management/subscriptions/create?mode=edit&id=${subscription.id}`}
                role="menuitem"
                aria-disabled={isBusy}
                className={`${MENU_ITEM_CLASS} text-app-text hover:bg-app-card-hover ${
                  isBusy ? "pointer-events-none opacity-50" : ""
                }`}
                onClick={(event) => {
                  event.stopPropagation();
                  if (isBusy) {
                    event.preventDefault();
                    return;
                  }
                  setOpen(false);
                }}
              >
                <PencilIcon className="size-4" />
                تعديل الاشتراك
              </Link>
            )}

            {canRenewSubscription && (
              <button
                type="button"
                role="menuitem"
                disabled={isBusy}
                className={`${MENU_ITEM_CLASS} text-app-yellow hover:bg-app-yellow/10`}
                onClick={() => runAction(onRenew)}
              >
                <RefreshIcon className="size-4" />
                تجديد الاشتراك
              </button>
            )}

            {canDelete && (
              <button
                type="button"
                role="menuitem"
                disabled={isBusy}
                className={`${MENU_ITEM_CLASS} text-app-red hover:bg-app-red/10`}
                onClick={() => runAction(onDelete)}
              >
                <TrashIcon className="size-4" />
                حذف الاشتراك
              </button>
            )}
          </div>,
          document.body,
        )}
    </div>
  );
}
