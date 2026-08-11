"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import Button from "@/components/ui/Button";
import { EyeIcon, EyeOffIcon } from "@/components/icons/Icons";
import BrandLogo from "@/components/common/BrandLogo";
import { loginSchema } from "@/lib/validations/authSchema";

const DEFAULT_FCM_TOKEN = "fcm_token_string_here";

export default function LoginForm() {
  const router = useRouter();
  const [isLoading, setIsLoading] = useState(false);
  const [form, setForm] = useState({
    username: "",
    password: "",
    remember: false,
  });
  const [fieldErrors, setFieldErrors] = useState({});
  const [errorMessage, setErrorMessage] = useState("");

  const [showPassword, setShowPassword] = useState(false);

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (fieldErrors[field]) {
      setFieldErrors((prev) => ({ ...prev, [field]: "" }));
    }
  }

  function validateForm() {
    const result = loginSchema.safeParse(form);
    if (!result.success) {
      const errors = {};
      result.error.issues.forEach((issue) => {
        const fieldName = issue.path[0];
        if (fieldName && !errors[fieldName]) {
          errors[fieldName] = issue.message;
        }
      });
      setFieldErrors(errors);
      return false;
    }

    setFieldErrors({});
    return true;
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setErrorMessage("");

    if (!validateForm()) {
      return;
    }

    setIsLoading(true);
    try {
      const response = await fetch("/api/backend/auth/login", {
        method: "POST",
        credentials: "same-origin",
        cache: "no-store",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          "X-Remember-Me": form.remember ? "true" : "false",
        },
        body: JSON.stringify({
          username: form.username.trim(),
          password: form.password,
          fcm_token: DEFAULT_FCM_TOKEN,
        }),
      });
      const payload = await response.json().catch(() => null);

      if (!response.ok) {
        throw new Error(payload?.message || "تعذر تسجيل الدخول. تحقق من البيانات وحاول مرة أخرى.");
      }

      router.replace("/management");
      router.refresh();
    } catch (error) {
      setForm((current) => ({ ...current, password: "" }));
      setErrorMessage(error?.message || "تعذر تسجيل الدخول. تحقق من البيانات وحاول مرة أخرى.");
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <form
      noValidate
      onSubmit={handleSubmit}
      autoComplete="on"
      className="card-shell mx-auto w-full max-w-md rounded-3xl p-6 md:p-8"
      dir="rtl"
    >
      <div className="mb-8 text-center">
        <BrandLogo src="/img/test_logo.png" className="mx-auto mb-5 h-16 w-40" />
        <h1 className="text-2xl font-semibold text-white">تسجيل الدخول</h1>
        <p className="mt-2 text-sm text-app-muted-light">ادخل إلى أنظمة TechnoGYM الإدارية</p>
      </div>

      <div className="space-y-4">
        <label htmlFor="username" className="block w-full text-right text-sm text-app-muted-light">
          <span className="block w-full text-right">اسم المستخدم</span>
          <input
            id="username"
            name="username"
            value={form.username}
            type="text"
            dir="rtl"
            autoComplete="username"
            autoCapitalize="none"
            spellCheck={false}
            minLength={3}
            maxLength={50}
            required
            onChange={(event) => updateField("username", event.target.value)}
            className={`mt-2 h-12 w-full rounded-xl border bg-app-card-soft px-4 text-right text-white outline-none transition focus:border-app-yellow ${
              fieldErrors.username ? "border-app-red" : "border-app-line"
            }`}
          />
          {fieldErrors.username && (
            <span className="mt-1.5 block text-xs text-app-red text-right w-full">
              {fieldErrors.username}
            </span>
          )}
        </label>

        <label
          htmlFor="password"
          className="relative block w-full text-right text-sm text-app-muted-light"
        >
          <span className="block w-full text-right">كلمة المرور</span>
          <div className="relative mt-2">
            <input
              id="password"
              name="password"
              value={form.password}
              type={showPassword ? "text" : "password"}
              dir="ltr"
              autoComplete="current-password"
              minLength={6}
              maxLength={100}
              required
              onChange={(event) => updateField("password", event.target.value)}
              className={`login-password-input h-12 w-full rounded-xl border bg-app-card-soft ps-4 pe-12 text-right text-white outline-none transition focus:border-app-yellow ${
                fieldErrors.password ? "border-app-red" : "border-app-line"
              }`}
            />
            <button
              type="button"
              onClick={() => setShowPassword((current) => !current)}
              className="absolute end-4 top-1/2 -translate-y-1/2 text-app-muted-light transition hover:text-white"
              aria-label={showPassword ? "إخفاء كلمة المرور" : "إظهار كلمة المرور"}
              aria-pressed={showPassword}
            >
              {showPassword ? <EyeIcon className="size-5" /> : <EyeOffIcon className="size-5" />}
            </button>
          </div>
          {fieldErrors.password && (
            <span className="mt-1.5 block text-xs text-app-red text-right w-full">
              {fieldErrors.password}
            </span>
          )}
        </label>
      </div>

      {errorMessage && (
        <p className="mt-4 rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
          {errorMessage}
        </p>
      )}

      <div className="mt-4 flex items-center justify-between text-xs text-app-muted-light">
        <Link href="/forgot-password" className="text-app-yellow hover:text-app-yellow/80">
          نسيت كلمة المرور؟
        </Link>
        <label className="inline-flex items-center gap-2">
          <span>تذكرني</span>
          <input
            type="checkbox"
            className="accent-[#f2dc2e]"
            checked={form.remember}
            onChange={(event) => updateField("remember", event.target.checked)}
          />
        </label>
      </div>

      <Button
        className="mt-7 h-12 w-full text-base"
        type="submit"
        loading={isLoading}
        loadingLabel="جاري تسجيل الدخول"
      >
        دخول
      </Button>
    </form>
  );
}
