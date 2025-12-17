import React, { useState } from 'react';
import { TextField, Button, Typography, Alert } from '@mui/material';
import { useNavigate, Link } from 'react-router-dom';
import { Extension, Google, GitHub, Apple } from '@mui/icons-material';
import { useAuth } from '../../contexts/AuthContext';

const Login = () => {
  const navigate = useNavigate();
  const { login } = useAuth();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleLogin = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      await login('test@test.com', 'password');
      navigate('/');
    } catch {
      setError('Failed to sign in.');
    } finally {
      setLoading(false);
    }
  };

  // Shared column width style
  const colSx = { width: '100%', maxWidth: 360, marginLeft: 'auto', marginRight: 'auto' };

  return (
    <div className="min-h-screen w-full bg-[#302E2B] flex items-center justify-center p-6">
      <div className="w-full items-center justify-center flex max-w-[420px] max-h-[90vh] overflow-y-auto bg-[#262421] rounded-xl shadow-2xl border border-[#383531] p-8 box-border">
        
        <div className="flex flex-col items-center justify-center w-full">
          
          {/* 1. LOGO HEADER: Forced Flex Center */}
          <div className="w-full flex flex-col justify-center items-center mb-6">
            <div className="flex items-center flex-col justify-center gap-2 text-center">
              <Extension sx={{ fontSize: 32, color: '#2C75FF' }} />
              <Typography variant="h5" sx={{ textAlign: 'center' }} className="font-bold text-white tracking-tight">
                CHESS<span className="text-[#2C75FF]">MASTER</span>
              </Typography>
            </div>
          </div>

          {/* SOCIAL BUTTONS */}
          <div className="w-full flex flex-col gap-3 m-3" style={colSx}>
            <Button
              variant="outlined"
              fullWidth
              startIcon={<Google />}
              disabled={loading}
              sx={{
                color: '#fff',
                borderColor: '#383531',
                backgroundColor: '#211F1C',
                textTransform: 'none',
                justifyContent: 'center',
                '&:hover': { borderColor: '#999', backgroundColor: '#2A2825' }
              }}
            >
              Log in with Google
            </Button>

            <Button
              variant="outlined"
              fullWidth
              startIcon={<GitHub />}
              disabled={loading}
              sx={{
                color: '#fff',
                borderColor: '#383531',
                backgroundColor: '#211F1C',
                justifyContent: 'center',
                '&:hover': { borderColor: '#999', backgroundColor: '#2A2825' }
              }}
            >
              Log in with GitHub
            </Button>
          </div>

          {/* 2. DIVIDER: Forced Flex Center */}
          {/* <div className="w-full flex justify-center items-center my-6">
            <div className="flex items-center justify-center w-full max-w-[360px]">
              <div className="h-px bg-[#383531] flex-1 justify-center text-center">
              <span className="px-3 text-xs text-[#666] font-bold text-center">OR</span>
              </div>  
            </div>
          </div> */}

          {/* FORM */}
          <form onSubmit={handleLogin} className="w-full flex flex-col gap-4 mt-4" style={colSx}>
            {error && (
              <Alert severity="error" sx={{ borderRadius: 1 }}>
                {error}
              </Alert>
            )}

            <TextField
              placeholder="Username or Email"
              fullWidth
              required
              disabled={loading}
              InputProps={{
                style: { color: '#e5e5e5', backgroundColor: '#1E1D1B', borderRadius: 6 }
              }}
              inputProps={{ style: { textAlign: 'center' } }}
              sx={{ '& fieldset': { border: '1px solid #383531' } }}
            />

            <TextField
              placeholder="Password"
              type="password"
              fullWidth
              required
              disabled={loading}
              InputProps={{
                style: { color: '#e5e5e5', backgroundColor: '#1E1D1B', borderRadius: 6 }
              }}
              inputProps={{ style: { textAlign: 'center' } }}
              sx={{ '& fieldset': { border: '1px solid #383531' } }}
            />

            <Button
              type="submit"
              variant="contained"
              fullWidth
              size="large"
              disabled={loading}
              sx={{
                fontWeight: 'bold',
                boxShadow: '0 4px 15px rgba(44, 117, 255, 0.3)',
                textTransform: 'none',
                fontSize: '1rem',
                padding: '12px',
                mt: 0.5
              }}
            >
              {loading ? 'Logging in...' : 'Log In'}
            </Button>
          </form>

          {/* 3. FOOTER: Forced Flex Center */}
          <div className="w-full flex flex-col items-center justify-center mt-6 text-center" style={colSx}>
            <Link
              to="/forgot-password"
              className="text-xs text-[#2C75FF] hover:text-white font-medium transition-colors mb-2 text-center"
            >
              Forgot Password?
            </Link>

            <Typography variant="body2" className="text-[#888] text-sm">
              New?{' '}
              <Link to="/register" className="text-white font-bold hover:text-[#2C75FF] transition-colors">
                Sign up
              </Link>
            </Typography>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Login;