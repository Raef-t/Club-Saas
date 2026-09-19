import { CURRENCY_SYMBOL, formatMoney } from "@/lib/utils";

function NumberField({ label, value, onChange, max, suffix, error }) {
  return (
    <label className="block text-right text-sm text-app-muted-light">
      {label}
      {suffix && <span className="ms-1 text-xs text-app-yellow">({suffix})</span>}
      <input
        type="number"
        min="0"
        max={max}
        step="0.01"
        value={value}
        onChange={(event) => onChange(event.target.value)}
        aria-invalid={Boolean(error)}
        className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${
          error ? "border-app-red focus:border-app-red" : "focus:border-app-yellow/70"
        }`}
      />
      {error && (
        <span className="mt-1.5 block text-xs text-app-red" role="alert">
          {error}
        </span>
      )}
    </label>
  );
}

/** Shared discount controls for create and edit subscription forms. */
export default function SubscriptionDiscountFields({
  form,
  originalTotal,
  coachOriginal,
  branchOriginal,
  isPrivatePlan,
  errors = {},
  onToggle,
  onModeChange,
  onFinalPriceChange,
  onPercentageChange,
  onCoachPercentageChange,
  onBranchPercentageChange,
  onReasonChange,
}) {
  return (
    <section className="space-y-3 rounded-xl border border-app-line bg-app-card-soft/40 p-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="text-right">
          <p className="text-xs text-app-muted-light">
            {form.is_discount ? "السعر الأصلي قبل الحسم" : "السعر الأساسي"}
          </p>
          <p className="mt-1 text-lg font-semibold text-app-text">{formatMoney(originalTotal)}</p>
        </div>

        <label className="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-app-text">
          <input
            type="checkbox"
            checked={form.is_discount}
            onChange={(event) => onToggle(event.target.checked)}
            className="size-4 accent-[var(--color-app-yellow)]"
          />
          تطبيق حسم
        </label>
      </div>

      {form.is_discount && (
        <div className="space-y-4 border-t border-app-line pt-4">
          {isPrivatePlan && (
            <fieldset className="grid grid-cols-2 gap-2" aria-label="طريقة توزيع الحسم">
              <button
                type="button"
                aria-pressed={form.discount_mode === "unified"}
                onClick={() => onModeChange("unified")}
                className={`rounded-lg border px-3 py-2 text-xs font-medium transition-colors ${
                  form.discount_mode === "unified"
                    ? "border-app-yellow bg-app-yellow/10 text-app-yellow"
                    : "border-app-line text-app-muted-light hover:text-app-text"
                }`}
              >
                حسم موحّد
              </button>
              <button
                type="button"
                aria-pressed={form.discount_mode === "split"}
                onClick={() => onModeChange("split")}
                className={`rounded-lg border px-3 py-2 text-xs font-medium transition-colors ${
                  form.discount_mode === "split"
                    ? "border-app-yellow bg-app-yellow/10 text-app-yellow"
                    : "border-app-line text-app-muted-light hover:text-app-text"
                }`}
              >
                حسم منفصل
              </button>
            </fieldset>
          )}

          {!isPrivatePlan || form.discount_mode === "unified" ? (
            <div className="grid gap-3 sm:grid-cols-2">
              <NumberField
                label="السعر النهائي بعد الحسم"
                suffix={CURRENCY_SYMBOL}
                value={form.final_price}
                max={originalTotal}
                onChange={onFinalPriceChange}
                error={errors.final_price}
              />
              <NumberField
                label="نسبة الحسم"
                suffix="%"
                value={form.discount_percentage}
                max="100"
                onChange={onPercentageChange}
                error={errors.discount_percentage}
              />
            </div>
          ) : (
            <div className="space-y-4">
              <div className="rounded-lg border border-app-line bg-black/10 p-3">
                <p className="mb-3 text-xs font-medium text-app-yellow">
                  حسم الكوتش — الأصل {formatMoney(coachOriginal)}
                </p>
                <NumberField
                  label="نسبة حسم الكوتش"
                  suffix="%"
                  value={form.coach_discount_percentage}
                  max="100"
                  onChange={onCoachPercentageChange}
                  error={errors.coach_discount_percentage}
                />
              </div>

              <div className="rounded-lg border border-app-line bg-black/10 p-3">
                <p className="mb-3 text-xs font-medium text-app-yellow">
                  حسم النادي — الأصل {formatMoney(branchOriginal)}
                </p>
                <NumberField
                  label="نسبة حسم النادي"
                  suffix="%"
                  value={form.branch_discount_percentage}
                  max="100"
                  onChange={onBranchPercentageChange}
                  error={errors.branch_discount_percentage}
                />
              </div>
            </div>
          )}

          <div className="grid gap-2 rounded-lg bg-black/15 p-3 text-xs sm:grid-cols-3">
            <p className="text-app-muted-light">
              قيمة الحسم:{" "}
              <span className="font-medium text-app-red">{formatMoney(form.discount_amount)}</span>
            </p>
            <p className="text-app-muted-light">
              الصافي:{" "}
              <span className="font-medium text-app-green">{formatMoney(form.final_price)}</span>
            </p>
            <p className="text-app-muted-light">
              النسبة:{" "}
              <span className="font-medium text-app-yellow">{form.discount_percentage || 0}%</span>
            </p>
          </div>

          <label className="block text-right text-sm text-app-muted-light">
            سبب الحسم <span className="text-app-red">*</span>
            <textarea
              rows={2}
              maxLength={500}
              value={form.discount_reason}
              onChange={(event) => onReasonChange(event.target.value)}
              placeholder="مثال: حسم خاص لمشتركة قديمة"
              aria-invalid={Boolean(errors.discount_reason)}
              className={`app-input mt-2 min-h-20 w-full resize-y bg-app-card-soft px-3 py-2 text-right text-white outline-none ${
                errors.discount_reason
                  ? "border-app-red focus:border-app-red"
                  : "focus:border-app-yellow/70"
              }`}
            />
            {errors.discount_reason && (
              <span className="mt-1.5 block text-xs text-app-red" role="alert">
                {errors.discount_reason}
              </span>
            )}
          </label>
        </div>
      )}
    </section>
  );
}
