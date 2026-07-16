import LockersEditClient from "./LockersEditClient";

export default async function EditLockerPage({ params }) {
  const { id } = await params;
  return <LockersEditClient lockerId={id} />;
}
