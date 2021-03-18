# InstaAPI

API de Busca Segmentada no Instagram (Para seguir perfis que se encaixam no parametro de busca)

Funcoes principais (controllers/Insta.php):

- login() - Login to Instagram account and update its session.
- i2fauth() - Finish Two Factor Authentication.
- challenge() - Finish Challenge (0 = SMS, 1 = Email).
- search() - Search for people, hashtags and locations.
- discover() - Find hashtags (#), locations and people (@) related to a search query.
- related() - Get users related to another user.
- suggestions() - Get suggestions of users to follow based on interests, location etc.

Requerimentos:

- PHP
- CodeIgniter
