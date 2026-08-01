import "./globals.css";
import { ThemeProvider } from "@/components/ThemeProvider";
import StoreProvider from "@/lib/StoreProvider";
import { ToastProvider } from "@/components/ui/Toast";
import { TimeFormatProvider } from "@/lib/TimeFormatContext";

export const metadata = {
  title: "TechnoGYM Dashboard",
  description: "Gym accounting and reports dashboard",
};

export default function RootLayout({ children }) {
  return (
    <html lang="ar" dir="rtl" suppressHydrationWarning>
      <body className="antialiased">
        <ThemeProvider attribute="class" defaultTheme="dark" enableSystem={false}>
          <TimeFormatProvider>
            <StoreProvider>
              <ToastProvider>{children}</ToastProvider>
            </StoreProvider>
          </TimeFormatProvider>
        </ThemeProvider>
      </body>
    </html>
  );
}
