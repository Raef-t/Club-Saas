import { createBackendApi } from "@/lib/api/baseQuery";

const OPTIONAL_PROFILE_FIELDS = ["dob", "address", "how_did_you_hear"];

export function createProfileUpdateBody(person = {}, values = {}) {
  const body = {
    first_name: values.first_name.trim(),
    last_name: values.last_name.trim(),
    phone_number: values.phone_number.trim(),
    gender: values.gender,
  };

  OPTIONAL_PROFILE_FIELDS.forEach((field) => {
    if (person[field] !== undefined && person[field] !== null) {
      body[field] = person[field];
    }
  });

  return body;
}

export const authApi = createBackendApi({
  reducerPath: "authApi",
  tagTypes: ["Profile"],
  endpoints: (builder) => ({
    logout: builder.mutation({
      query: () => ({
        url: "auth/logout",
        method: "POST",
      }),
    }),
    changePassword: builder.mutation({
      query: (body) => ({
        url: "auth/change-password",
        method: "POST",
        body,
      }),
    }),
    getProfile: builder.query({
      query: () => "auth/me",
      providesTags: ["Profile"],
    }),
    resetPassword: builder.mutation({
      query: (body) => ({
        url: "auth/reset-password",
        method: "POST",
        body,
      }),
    }),
    updateProfile: builder.mutation({
      query: (body) => ({
        url: "auth/profile",
        method: "PUT",
        body,
      }),
      invalidatesTags: ["Profile"],
    }),
  }),
});

export const {
  useLogoutMutation,
  useChangePasswordMutation,
  useGetProfileQuery,
  useResetPasswordMutation,
  useUpdateProfileMutation,
} = authApi;
