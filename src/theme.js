import { createTheme } from '@mui/material/styles';

// Cherenkov Blue Palette
const cherenkovBlue = {
  main: '#2C75FF', // Electric Blue
  light: '#6FA3FF',
  dark: '#0056E0',
  contrastText: '#ffffff',
};

export const theme = createTheme({
  palette: {
    mode: 'dark', // <--- THIS IS THE KEY FIX
    primary: cherenkovBlue,
    background: {
      default: '#302E2B',
      paper: '#262421',
    },
    text: {
      primary: '#e5e5e5', // Light gray for reading
      secondary: '#a3a3a3', // Dimmer gray for labels
    },
  },
  typography: {
    fontFamily: '"Segoe UI", "Inter", "Helvetica Neue", sans-serif',
    allVariants: {
      color: '#e5e5e5', // Forces all Material Text to be light
    },
    h5: {
      fontWeight: 700,
    },
  },
  components: {
    // Fix Input Fields (TextField) to have light text and borders
    MuiOutlinedInput: {
      styleOverrides: {
        root: {
          color: '#ffffff',
          '& .MuiOutlinedInput-notchedOutline': {
            borderColor: '#383531', // Dark border
          },
          '&:hover .MuiOutlinedInput-notchedOutline': {
            borderColor: '#999', // Light grey on hover
          },
          '&.Mui-focused .MuiOutlinedInput-notchedOutline': {
            borderColor: '#2C75FF', // Blue on focus
          },
        },
      },
    },
    // Fix Input Labels
    MuiInputLabel: {
      styleOverrides: {
        root: {
          color: '#a3a3a3',
          '&.Mui-focused': {
            color: '#2C75FF',
          },
        },
      },
    },
    // Fix Buttons
    MuiButton: {
      styleOverrides: {
        root: {
          textTransform: 'none',
          fontWeight: 600,
        },
      },
    },
  },
});