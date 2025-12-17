import React, { useState } from 'react';
import { TextField, Button, Typography, Alert } from '@mui/material';
import { useNavigate, Link } from 'react-router-dom';
import { Extension, Google, GitHub, Apple } from '@mui/icons-material';
// import { useAuth } from '../../contexts/AuthContext'; // Uncomment when AuthContext has register

const Register = () => {
  const navigate = useNavigate();
  // const { register } = useAuth(); // Unrealized context method
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  
  const [formData, setFormData] = useState({
    username: '',
    email: '',
    password: ''
  });

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleRegister = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      // Simulate API call
      // await register(formData.email, formData.password, formData.username);
      console.log("Registering:", formData);
      navigate('/login');
    } catch (err) {
      setError('Failed to create account.');
    } finally {
      setLoading(false);
    }
  };

  // Same column width for inputs/buttons to match Login page exactly
  const colSx = { width: '100%', maxWidth: 360, marginLeft: 'auto', marginRight: 'auto' };

  return (
    <div className="min-h-screen w-full bg-[#302E2B] flex items-center justify-center p-6">
      <div className="w-full max-w-[420px] max-h-[90vh] overflow-y-auto bg-[#262421] rounded-xl shadow-2xl border border-[#383531] p-8 box-border">
        {/* ONE column */}
        <div className="flex flex-col items-center w-full">
          
          {/* Header: FORCE centered via flex */}
          <div className="w-full flex flex-col justify-center items-center mb-6">
            <div className="flex items-center justify-center gap-2 text-center">
              <Extension sx={{ fontSize: 32, color: '#2C75FF' }} />
              <Typography variant="h5" sx={{ textAlign: 'center' }} className="font-bold text-white tracking-tight">
                CHESS<span className="text-[#2C75FF]">MASTER</span>
              </Typography>
            </div>
            <Typography variant="body2" className="text-[#888] text-xs mt-2 font-medium tracking-wide uppercase">
              Join the Ranks
            </Typography>
          </div>

          {/* Social: centered column */}
          <div className="w-full flex flex-col gap-3 mb-6" style={colSx}>
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
              Sign up with Google
            </Button>

            <div className="flex gap-3 w-full">
              <Button
                variant="outlined"
                fullWidth
                startIcon={<Apple />}
                disabled={loading}
                sx={{
                  color: '#fff',
                  borderColor: '#383531',
                  backgroundColor: '#211F1C',
                  justifyContent: 'center',
                  '&:hover': { borderColor: '#999', backgroundColor: '#2A2825' }
                }}
              />
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
              />
            </div>
          </div>

          {/* Divider: FORCE centered via flex container + fixed width */}
          <div className="w-full flex justify-center my-6">
            <div className="flex flex-column items-center justify-center w-full max-w-[360px]">
              <div className="h-px bg-[#383531] flex-1" />
              <span className="px-3 text-xs text-[#666] font-bold text-center">OR</span>
              <div className="h-px bg-[#383531] flex-1" />
            </div>
          </div>

          {/* Form: centered column */}
          <form onSubmit={handleRegister} className="w-full flex flex-col gap-4" style={colSx}>
            {error && (
              <Alert severity="error" sx={{ borderRadius: 1 }}>
                {error}
              </Alert>
            )}

            <TextField
              placeholder="Username"
              name="username"
              fullWidth
              required
              disabled={loading}
              value={formData.username}
              onChange={handleChange}
              InputProps={{
                style: { color: '#e5e5e5', backgroundColor: '#1E1D1B', borderRadius: 6 }
              }}
              inputProps={{ style: { textAlign: 'center' } }}
              sx={{ '& fieldset': { border: '1px solid #383531' } }}
            />

            <TextField
              placeholder="Email Address"
              name="email"
              type="email"
              fullWidth
              required
              disabled={loading}
              value={formData.email}
              onChange={handleChange}
              InputProps={{
                style: { color: '#e5e5e5', backgroundColor: '#1E1D1B', borderRadius: 6 }
              }}
              inputProps={{ style: { textAlign: 'center' } }}
              sx={{ '& fieldset': { border: '1px solid #383531' } }}
            />

            <TextField
              placeholder="Password"
              name="password"
              type="password"
              fullWidth
              required
              disabled={loading}
              value={formData.password}
              onChange={handleChange}
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
              {loading ? 'Creating Account...' : 'Sign Up'}
            </Button>
          </form>

          {/* Footer: FORCE centered */}
          <div className="w-full flex flex-col items-center mt-6" style={colSx}>
            <Typography variant="body2" sx={{ textAlign: 'center', mt: 1 }} className="text-[#888] text-sm">
              Already have an account?{' '}
              <Link to="/login" className="text-white font-bold hover:text-[#2C75FF] transition-colors">
                Log In
              </Link>
            </Typography>
          </div>

        </div>
      </div>
    </div>
  );
};

export default Register;