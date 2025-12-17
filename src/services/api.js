export const api = {
  login: async (email, password) => {
    // Simulate network delay
    return new Promise((resolve) => {
      setTimeout(() => resolve({ id: 1, name: "Ēriks", email, rank: 1200 }), 800);
    });
  },
  getStats: async () => {
    return [
      { name: 'Mon', rating: 1200 }, { name: 'Tue', rating: 1215 },
      { name: 'Wed', rating: 1210 }, { name: 'Thu', rating: 1240 },
      { name: 'Fri', rating: 1235 }, { name: 'Sat', rating: 1255 },
      { name: 'Sun', rating: 1280 },
    ];
  }
};