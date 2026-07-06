import Button from "@/components/ui/Button";
import { ChevronRight, PlusIcon } from "@/components/icons/Icons";

export default function ManagementCreatePage({
  title,
  subtitle,
  formId,
  backHref,
  children,
  isSubmitting = false,
  submitLabel = "حفظ",
  backLabel = "رجوع",
  maxWidth = "1040px",
}) {
  return (
    <div
      className="management-create-page space-y-6"
      dir="rtl"
      style={{ "--entry-page-max": maxWidth }}
    >
      <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div className="text-right">
          <h1 className="text-2xl font-medium text-app-text">{title}</h1>
          {subtitle ? (
            <p className="mt-1 text-sm text-app-muted-light">{subtitle}</p>
          ) : null}
        </div>

        <div className="flex items-center gap-3 self-start md:self-auto">
          <Button
            type="submit"
            form={formId}
            className="h-[34px] px-7"
            icon={<PlusIcon className="size-4" />}
            loading={isSubmitting}
          >
            {submitLabel}
          </Button>
          {backHref ? (
            <Button
              href={backHref}
              tone="dark"
              className="h-[34px] px-5"
              icon={<ChevronRight className="size-4" />}
            >
              {backLabel}
            </Button>
          ) : null}
        </div>
      </div>

      <div className="entry-form-surface">{children}</div>
    </div>
  );
}
