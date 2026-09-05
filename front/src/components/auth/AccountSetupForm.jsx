"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import BrandLogo from "@/components/common/BrandLogo";
import Button from "@/components/ui/Button";
import { EyeIcon, EyeOffIcon } from "@/components/icons/Icons";

const INITIAL_FORM = {
  custom_username: "",
  new_password: "",
  new_password_confirmation: "",
};

function PasswordField({ id, label, value, error, autoComplete, onChange }) {
  const [isVisible, setIsVisible] = useState(false);

  return (
    <label htmlFor={id} className="block text-sm text-app-muted-light">
      <span>{label}</span>
      <div className="relative mt-2">
        <input
          id={id}
          name={id}
          value={value}
          type={isVisible ? "text" : "password"}
          dir="ltr"
          autoComplete={autoComplete}
          onChange={(event) => onChange(event.target.value)}
          className={`login-password-input h-12 w-full rounded-xl border bg-app-card-soft px-4 pe-12 text-left text-white outline-none transition focus:border-app-yellow ${
            error ? "border-app-red" : "border-app-line"
          }`}
          aria-invalid={Boolean(error)}
          aria-describedby={error ? `${id}-error` : undefined}
        />
        <button
          type="button"
          onClick={() => setIsVisible((current) => !current)}
          className="absolute end-4 top-1/2 -translate-y-1/2 text-app-muted-light transition hover:text-white"
          aria-label={isVisible ? `إخفاء ${label}` : `إظهار ${label}`}
          aria-pressed={isVisible}
        >
          {isVisible ? <EyeIcon className="size-5" /> : <EyeOffIcon className="size-5" />}
        </button>
      </div>
      {error && (
        <span id={`${id}-error`} className="mt-1.5 block text-xs text-app-red">
          {error}
        </span>
      )}
    </label>
  );
}

export default function AccountSetupForm({ userId, displayName, systemUsername }) {
  const router = useRouter();
  const [form, setForm] = useState(INITIAL_FORM);
  const [fieldErrors, setFieldErrors] = useState({});
  const [usernameSuggestions, setUsernameSuggestions] = useState([]);
  const [errorMessage, setErrorMessage] = useState("");
  const [isSaving, setIsSaving] = useState(false);
  const [isSigningOut, setIsSigningOut] = useState(false);

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    setErrorMessage("");
    if (field === "custom_username") {
      setUsernameSuggestions([]);
    }
    if (fieldErrors[field]) {
      setFieldErrors((current) => ({ ...current, [field]: "" }));
    }
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setErrorMessage("");

    setFieldErrors({});
    setUsernameSuggestions([]);
    setIsSaving(true);

    try {
      const response = await fetch("/api/backend/auth/change-password", {
        method: "POST",
        credentials: "same-origin",
        cache: "no-store",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          user_id: Number(userId),
          new_password: form.new_password,
          new_password_confirmation: form.new_password_confirmation,
          custom_username: form.custom_username,
        }),
      });
      const payload = await response.json().catch(() => null);

      if (!response.ok) {
        const backendErrors = payload?.errors || {};
        const backendSuggestions = payload?.data?.suggestions;
        setFieldErrors(
          Object.fromEntries(
            Object.entries(backendErrors).map(([field, messages]) => [
              field,
              Array.isArray(messages) ? String(messages[0] || "") : String(messages || ""),
            ]),
          ),
        );
        setUsernameSuggestions(
          Array.isArray(backendSuggestions)
            ? backendSuggestions.filter((suggestion) => typeof suggestion === "string")
            : [],
        );
        throw new Error(payload?.message || "تعذر حفظ بيانات الحساب. حاول مرة أخرى.");
      }

      router.replace("/");
      router.refresh();
    } catch (error) {
      setErrorMessage(error?.message || "تعذر حفظ بيانات الحساب. حاول مرة أخرى.");
    } finally {
      setIsSaving(false);
    }
  }

  async function handleSignOut() {
    setIsSigningOut(true);
    try {
      await fetch("/api/backend/auth/logout", {
        method: "POST",
        credentials: "same-origin",
        cache: "no-store",
      });
    } finally {
      window.location.replace("/login");
    }
  }

  return (
    <main className="dashboard-bg relative grid min-h-screen place-items-center overflow-hidden px-4 py-10">
      <div className="pointer-events-none absolute inset-0 opacity-70" aria-hidden="true">
        <div className="absolute -end-32 -top-32 size-96 rounded-full bg-app-yellow/10 blur-3xl" />
        <div className="absolute -bottom-48 -start-24 size-[30rem] rounded-full bg-app-blue/10 blur-3xl" />
      </div>

      <section className="card-shell relative w-full max-w-2xl overflow-hidden rounded-3xl border border-app-line">
        <div className="h-1 w-full bg-gradient-to-l from-app-yellow via-app-yellow/80 to-app-blue" />
        <div className="p-6 sm:p-9">
          <div className="flex flex-col gap-5 border-b border-app-line pb-7 sm:flex-row sm:items-center">
            <BrandLogo
              className="h-14 w-36 shrink-0 bg-black/20 ring-1 ring-app-yellow/15"
              preload
            />
            <div>
              <p className="text-sm font-medium text-app-yellow">خطوة أخيرة قبل البدء</p>
              <h1 className="mt-1 text-2xl font-semibold text-white sm:text-3xl">
                أهلاً {displayName || "بك"}
              </h1>
              <p className="mt-2 text-sm leading-6 text-app-muted-light">
                أنشئ بيانات دخول خاصة بك لحماية حسابك وتسهيل الدخول لاحقًا.
              </p>
            </div>
          </div>

          <form noValidate onSubmit={handleSubmit} className="mt-7" dir="rtl">
            <div className="grid gap-5 sm:grid-cols-2">
              <div className="sm:col-span-2">
                <label htmlFor="custom_username" className="block text-sm text-app-muted-light">
                  <span>اسم المستخدم الجديد</span>
                  <input
                    id="custom_username"
                    name="custom_username"
                    value={form.custom_username}
                    type="text"
                    dir="ltr"
                    autoComplete="username"
                    autoCapitalize="none"
                    spellCheck={false}
                    placeholder="مثال: ahmed.player"
                    onChange={(event) => updateField("custom_username", event.target.value)}
                    className={`mt-2 h-12 w-full rounded-xl border bg-app-card-soft px-4 text-left text-white outline-none transition placeholder:text-app-muted focus:border-app-yellow ${
                      fieldErrors.custom_username ? "border-app-red" : "border-app-line"
                    }`}
                    aria-invalid={Boolean(fieldErrors.custom_username)}
                  />
                  <span className="mt-1.5 block text-xs text-app-muted">
                    ستستخدمه بدلًا من المعرّف الحالي {systemUsername ? `(${systemUsername})` : ""}
                  </span>
                  {fieldErrors.custom_username && (
                    <span className="mt-1 block text-xs text-app-red">
                      {fieldErrors.custom_username}
                    </span>
                  )}
                </label>

                {usernameSuggestions.length > 0 && (
                  <div className="mt-3" aria-label="اقتراحات أسماء المستخدم">
                    <p className="text-xs text-app-muted-light">اقتراحات متاحة:</p>
                    <div className="mt-2 flex flex-wrap gap-2">
                      {usernameSuggestions.map((suggestion) => (
                        <button
                          key={suggestion}
                          type="button"
                          dir="auto"
                          onClick={() => updateField("custom_username", suggestion)}
                          className="rounded-lg border border-app-yellow/40 bg-app-yellow/10 px-3 py-1.5 text-xs text-app-yellow transition hover:border-app-yellow hover:bg-app-yellow/20"
                        >
                          {suggestion}
                        </button>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              <PasswordField
                id="new_password"
                label="كلمة المرور الجديدة"
                value={form.new_password}
                error={fieldErrors.new_password}
                autoComplete="new-password"
                onChange={(value) => updateField("new_password", value)}
              />
              <PasswordField
                id="new_password_confirmation"
                label="تأكيد كلمة المرور الجديدة"
                value={form.new_password_confirmation}
                error={fieldErrors.new_password_confirmation}
                autoComplete="new-password"
                onChange={(value) => updateField("new_password_confirmation", value)}
              />
            </div>

            {errorMessage && (
              <p
                role="alert"
                className="mt-5 rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-sm text-app-red"
              >
                {errorMessage}
              </p>
            )}

            <div className="mt-7 flex flex-col-reverse gap-3 border-t border-app-line pt-6 sm:flex-row sm:justify-between">
              <button
                type="button"
                onClick={handleSignOut}
                disabled={isSaving || isSigningOut}
                className="h-12 rounded-xl px-5 text-sm text-app-muted-light transition hover:bg-app-card-soft hover:text-white disabled:opacity-60"
              >
                {isSigningOut ? "جاري تسجيل الخروج..." : "تسجيل الخروج"}
              </button>
              <Button
                type="submit"
                className="h-12 min-w-48 rounded-xl font-semibold"
                loading={isSaving}
                loadingLabel="جاري حفظ البيانات"
              >
                حفظ ومتابعة
              </Button>
            </div>
          </form>
        </div>
      </section>
    </main>
  );
}
