"use client";

import { useState } from "react";
import Image from "next/image";
import { useRouter } from "next/navigation";
import Button from "@/components/ui/Button";
import { useLoginMutation } from "@/lib/api/authApi";

const DEFAULT_FCM_TOKEN = "fcm_token_string_here";

function saveAuthData(payload) {
  const authData = payload?.data;

  if (!authData?.access_token) {
    throw new Error("Login response does not include an access token.");
  }

  window.localStorage.setItem("access_token", authData.access_token);
  window.localStorage.setItem("token_type", authData.token_type || "Bearer");
  window.localStorage.setItem("auth_user", JSON.stringify(authData.user || {}));
  window.localStorage.setItem("auth_session", JSON.stringify(authData));
}

export default function LoginForm() {
  const router = useRouter();
  const [login, { isLoading }] = useLoginMutation();
  const [form, setForm] = useState({
    username: "player",
    password: "password123",
    remember: true,
  });
  const [errorMessage, setErrorMessage] = useState("");

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setErrorMessage("");

    try {
      const response = await login({
        username: form.username,
        password: form.password,
        fcm_token: DEFAULT_FCM_TOKEN,
      }).unwrap();

      saveAuthData(response);
      router.push("/management/subscriptions");
    } catch (error) {
      setErrorMessage(
        error?.data?.message || "تعذر تسجيل الدخول. تحقق من البيانات وحاول مرة أخرى.",
      );
    }
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="card-shell w-full max-w-md rounded-3xl p-6 md:p-8"
    >
      <div className="mb-8 text-center">
        <div className="mx-auto mb-5 grid h-16 w-40 place-items-center rounded-2xl bg-black/30 ring-1 ring-app-yellow/20">
          <Image src="/img/logo.jpeg" alt="TechnoGYM" width={500} height={500} />
        </div>
        <h1 className="text-2xl font-semibold text-white">تسجيل الدخول</h1>
        <p className="mt-2 text-sm text-app-muted-light">
          ادخل إلى أنظمة TechnoGYM الإدارية
        </p>
      </div>

      <div className="space-y-4">
        <label className="block text-end text-sm text-app-muted-light">
          اسم المستخدم
          <input
            value={form.username}
            type="text"
            autoComplete="username"
            onChange={(event) => updateField("username", event.target.value)}
            className="mt-2 h-12 w-full rounded-xl border border-app-line bg-app-card-soft px-4 text-end text-white outline-none transition focus:border-app-yellow"
          />
        </label>

        <label className="block text-end text-sm text-app-muted-light">
          كلمة المرور
          <input
            value={form.password}
            type="password"
            autoComplete="current-password"
            onChange={(event) => updateField("password", event.target.value)}
            className="mt-2 h-12 w-full rounded-xl border border-app-line bg-app-card-soft px-4 text-end text-white outline-none transition focus:border-app-yellow"
          />
        </label>
      </div>

      {errorMessage && (
        <p className="mt-4 rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
          {errorMessage}
        </p>
      )}

      <div className="mt-4 flex items-center justify-between text-xs text-app-muted-light">
        <a href="#" className="text-app-yellow hover:text-app-yellow/80">
          نسيت كلمة المرور؟
        </a>
        <label className="inline-flex items-center gap-2">
          <span>تذكرني</span>
          <input
            type="checkbox"
            className="accent-[#fccd03]"
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
