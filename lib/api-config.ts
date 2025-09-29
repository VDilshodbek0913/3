export const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || "https://stacknet.site/blogpost/api"

export const apiEndpoints = {
  posts: `${API_BASE_URL}/api.php?action=posts`,
  login: `${API_BASE_URL}/api.php?action=login`,
  register: `${API_BASE_URL}/api.php?action=register`,
  verifyEmail: `${API_BASE_URL}/api.php?action=verify-email`,
  like: `${API_BASE_URL}/api.php?action=like`,
  comments: `${API_BASE_URL}/api.php?action=comments`,
  newsletterSubscribe: `${API_BASE_URL}/api.php?action=newsletter-subscribe`,
  captcha: `${API_BASE_URL}/captcha.php`,
  uploads: `${API_BASE_URL}/uploads`,
  adminPanel: `${API_BASE_URL}/panel.php`,
}

export const apiCall = async (url: string, options?: RequestInit) => {
  try {
    const response = await fetch(url, {
      ...options,
      headers: {
        "Content-Type": "application/json",
        ...options?.headers,
      },
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()
    return data
  } catch (error) {
    console.error("API call failed:", error)
    throw error
  }
}
